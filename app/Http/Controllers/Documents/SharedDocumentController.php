<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentShareLink;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SharedDocumentController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    /**
     * Show a shared document (public access, no auth required).
     */
    public function show(Request $request, string $token): JsonResponse|InertiaResponse
    {
        $shareLink = DocumentShareLink::with('document')
            ->byToken($token)
            ->first();

        if ($shareLink === null) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Share link not found.',
                ], 404);
            }

            return Inertia::render('shared/not-found');
        }

        // Check if link is expired
        if ($shareLink->isExpired()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'This share link has expired.',
                ], 410); // 410 Gone
            }

            return Inertia::render('shared/expired', [
                'expiresAt' => $shareLink->expires_at->toIso8601String(),
            ]);
        }

        // Check if password is required
        if ($shareLink->hasPassword()) {
            // If this is a JSON request, return password required status
            if ($request->wantsJson()) {
                return response()->json([
                    'requiresPassword' => true,
                    'documentName' => $shareLink->document->name,
                ]);
            }

            // Render password prompt page
            return Inertia::render('shared/password-required', [
                'token' => $token,
                'documentName' => $shareLink->document->name,
            ]);
        }

        // No password required - track access and show document
        return $this->showDocument($request, $shareLink);
    }

    /**
     * Verify password and show document.
     */
    public function verify(Request $request, string $token): JsonResponse|InertiaResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $shareLink = DocumentShareLink::with('document')
            ->byToken($token)
            ->first();

        if ($shareLink === null) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Share link not found.',
                ], 404);
            }

            return Inertia::render('shared/not-found');
        }

        // Check if link is expired
        if ($shareLink->isExpired()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'This share link has expired.',
                ], 410);
            }

            return Inertia::render('shared/expired', [
                'expiresAt' => $shareLink->expires_at->toIso8601String(),
            ]);
        }

        // Verify password
        if (! $shareLink->verifyPassword($validated['password'])) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Invalid password.',
                ], 403);
            }

            return Inertia::render('shared/password-required', [
                'token' => $token,
                'documentName' => $shareLink->document->name,
                'error' => 'Invalid password. Please try again.',
            ]);
        }

        // Password is correct - track access and show document
        return $this->showDocument($request, $shareLink);
    }

    /**
     * Generate a download URL for the shared document.
     */
    public function download(Request $request, string $token): JsonResponse
    {
        $shareLink = DocumentShareLink::with('document')
            ->byToken($token)
            ->first();

        if ($shareLink === null) {
            return response()->json([
                'error' => 'Share link not found.',
            ], 404);
        }

        // Check if link is expired
        if ($shareLink->isExpired()) {
            return response()->json([
                'error' => 'This share link has expired.',
            ], 410);
        }

        // Check if downloads are allowed
        if (! $shareLink->allow_download) {
            return response()->json([
                'error' => 'Downloads are not allowed for this share link.',
            ], 403);
        }

        // If password protected, verify session or require password
        if ($shareLink->hasPassword()) {
            $sessionKey = "shared_document_verified_{$shareLink->id}";
            if (! $request->session()->get($sessionKey, false)) {
                return response()->json([
                    'error' => 'Password verification required.',
                    'requiresPassword' => true,
                ], 403);
            }
        }

        $document = $shareLink->document;
        $downloadUrl = $this->getPreviewUrl($document->file_url);

        return response()->json([
            'downloadUrl' => $downloadUrl,
            'fileName' => $document->name,
        ]);
    }

    /**
     * Show the document after access validation.
     */
    private function showDocument(Request $request, DocumentShareLink $shareLink): JsonResponse|InertiaResponse
    {
        // Track access
        $shareLink->recordAccess(
            $request->ip(),
            $request->userAgent()
        );

        // Mark as verified in session for password-protected links
        if ($shareLink->hasPassword()) {
            $sessionKey = "shared_document_verified_{$shareLink->id}";
            $request->session()->put($sessionKey, true);
        }

        $document = $shareLink->document;

        // Generate signed URL for document preview
        $previewUrl = $this->getPreviewUrl($document->file_url);

        $documentData = [
            'id' => (string) $document->id,
            'name' => $document->name,
            'type' => $document->type->value,
            'fileSize' => $document->file_size,
            'previewUrl' => $previewUrl,
            'allowDownload' => $shareLink->allow_download,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'document' => $documentData,
            ]);
        }

        return Inertia::render('shared/document', [
            'document' => $documentData,
            'token' => $shareLink->token,
        ]);
    }

    /**
     * Get preview URL for a document.
     * Uses signed URL for S3 or regular URL for local storage.
     */
    private function getPreviewUrl(string $fileUrl): string
    {
        $defaultDisk = $this->fileUploadService->getDefaultDocumentDisk();

        // Check if S3 is properly configured
        if ($defaultDisk === 's3') {
            $s3Configured = config('filesystems.disks.s3.key')
                && config('filesystems.disks.s3.secret')
                && config('filesystems.disks.s3.bucket');

            if ($s3Configured) {
                return $this->fileUploadService->getSignedUrl($fileUrl, 's3', 60);
            }
        }

        // Fall back to public disk URL
        return Storage::disk('public')->url($fileUrl);
    }
}
