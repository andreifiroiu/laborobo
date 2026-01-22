<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentShareLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DocumentShareLinkController extends Controller
{
    /**
     * List share links for a document.
     */
    public function index(Request $request, Document $document): JsonResponse
    {
        $this->authorize('share', $document);

        $shareLinks = $document->shareLinks()
            ->with(['creator', 'accesses' => function ($query) {
                $query->orderByDesc('accessed_at')->limit(10);
            }])
            ->withCount('accesses')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'shareLinks' => $shareLinks->map(fn (DocumentShareLink $link) => $this->formatShareLink($link)),
        ]);
    }

    /**
     * Create a new share link.
     */
    public function store(Request $request, Document $document): JsonResponse|RedirectResponse
    {
        $this->authorize('share', $document);

        $validated = $request->validate([
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'allow_download' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        $shareLink = DocumentShareLink::create([
            'document_id' => $document->id,
            'token' => DocumentShareLink::generateToken(),
            'expires_at' => isset($validated['expires_in_days'])
                ? now()->addDays($validated['expires_in_days'])
                : null,
            'password_hash' => isset($validated['password'])
                ? Hash::make($validated['password'])
                : null,
            'allow_download' => $validated['allow_download'] ?? true,
            'created_by_id' => $user->id,
        ]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            $shareLink->load('creator');

            return response()->json([
                'shareLink' => $this->formatShareLink($shareLink),
                'url' => $shareLink->getUrl(),
            ], 201);
        }

        return back();
    }

    /**
     * Get a single share link's details with access log.
     */
    public function show(Request $request, Document $document, DocumentShareLink $shareLink): JsonResponse
    {
        $this->authorize('share', $document);
        $this->ensureShareLinkBelongsToDocument($document, $shareLink);

        $shareLink->load([
            'creator',
            'accesses' => function ($query) {
                $query->orderByDesc('accessed_at');
            },
        ]);
        $shareLink->loadCount('accesses');

        return response()->json([
            'shareLink' => $this->formatShareLink($shareLink, true),
        ]);
    }

    /**
     * Update a share link.
     */
    public function update(Request $request, Document $document, DocumentShareLink $shareLink): JsonResponse|RedirectResponse
    {
        $this->authorize('share', $document);
        $this->ensureShareLinkBelongsToDocument($document, $shareLink);

        $validated = $request->validate([
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'remove_password' => ['sometimes', 'boolean'],
            'allow_download' => ['sometimes', 'boolean'],
        ]);

        $updateData = [];

        // Handle expiration
        if (array_key_exists('expires_in_days', $validated)) {
            $updateData['expires_at'] = $validated['expires_in_days'] !== null
                ? now()->addDays($validated['expires_in_days'])
                : null;
        }

        // Handle password
        if (isset($validated['remove_password']) && $validated['remove_password'] === true) {
            $updateData['password_hash'] = null;
        } elseif (isset($validated['password'])) {
            $updateData['password_hash'] = Hash::make($validated['password']);
        }

        // Handle download permission
        if (isset($validated['allow_download'])) {
            $updateData['allow_download'] = $validated['allow_download'];
        }

        if (! empty($updateData)) {
            $shareLink->update($updateData);
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            $shareLink->load('creator');

            return response()->json([
                'shareLink' => $this->formatShareLink($shareLink),
            ]);
        }

        return back();
    }

    /**
     * Delete (revoke) a share link.
     */
    public function destroy(Request $request, Document $document, DocumentShareLink $shareLink): JsonResponse|RedirectResponse
    {
        $this->authorize('share', $document);
        $this->ensureShareLinkBelongsToDocument($document, $shareLink);

        // Delete access records first (due to foreign key constraint)
        $shareLink->accesses()->delete();
        $shareLink->delete();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(null, 204);
        }

        return back();
    }

    /**
     * Format a share link for API response.
     *
     * @return array<string, mixed>
     */
    private function formatShareLink(DocumentShareLink $link, bool $includeAccessLog = false): array
    {
        $data = [
            'id' => (string) $link->id,
            'documentId' => (string) $link->document_id,
            'token' => $link->token,
            'url' => $link->getUrl(),
            'expiresAt' => $link->expires_at?->toIso8601String(),
            'isExpired' => $link->isExpired(),
            'hasPassword' => $link->hasPassword(),
            'allowDownload' => $link->allow_download,
            'accessCount' => $link->accesses_count ?? $link->accesses->count(),
            'createdAt' => $link->created_at->toIso8601String(),
            'creator' => $link->creator ? [
                'id' => (string) $link->creator->id,
                'name' => $link->creator->name,
            ] : null,
        ];

        // Include recent access preview
        if ($link->relationLoaded('accesses') && ! $includeAccessLog) {
            $recentAccesses = $link->accesses->take(3);
            $data['recentAccesses'] = $recentAccesses->map(fn ($access) => [
                'accessedAt' => $access->accessed_at->toIso8601String(),
                'ipAddress' => $access->ip_address,
            ])->all();
        }

        // Include full access log if requested
        if ($includeAccessLog && $link->relationLoaded('accesses')) {
            $data['accessLog'] = $link->accesses->map(fn ($access) => [
                'id' => (string) $access->id,
                'accessedAt' => $access->accessed_at->toIso8601String(),
                'ipAddress' => $access->ip_address,
                'userAgent' => $access->user_agent,
            ])->all();
        }

        return $data;
    }

    /**
     * Ensure the share link belongs to the document.
     */
    private function ensureShareLinkBelongsToDocument(Document $document, DocumentShareLink $shareLink): void
    {
        if ($shareLink->document_id !== $document->id) {
            abort(404, 'Share link not found for this document.');
        }
    }
}
