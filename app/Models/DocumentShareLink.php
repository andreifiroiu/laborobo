<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DocumentShareLink extends Model
{
    use HasFactory;

    /**
     * Token length for share links.
     */
    public const TOKEN_LENGTH = 64;

    protected $fillable = [
        'document_id',
        'token',
        'expires_at',
        'password_hash',
        'allow_download',
        'created_by_id',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'expires_at' => 'datetime',
        'allow_download' => 'boolean',
        'created_by_id' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the document this share link belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the access records for this share link.
     */
    public function accesses(): HasMany
    {
        return $this->hasMany(DocumentShareAccess::class);
    }

    /**
     * Get the user who created this share link.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Scope to filter active (non-expired) share links.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to filter expired share links.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to filter share links by document.
     */
    public function scopeForDocument(Builder $query, int $documentId): Builder
    {
        return $query->where('document_id', $documentId);
    }

    /**
     * Scope to find share link by token.
     */
    public function scopeByToken(Builder $query, string $token): Builder
    {
        return $query->where('token', $token);
    }

    /**
     * Generate a cryptographically secure token for share links.
     */
    public static function generateToken(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * Check if this share link has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if this share link is still valid (not expired).
     */
    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Check if this share link is protected by a password.
     */
    public function hasPassword(): bool
    {
        return $this->password_hash !== null;
    }

    /**
     * Verify a password against this share link's password hash.
     */
    public function verifyPassword(string $password): bool
    {
        if (! $this->hasPassword()) {
            return true;
        }

        return Hash::check($password, $this->password_hash);
    }

    /**
     * Set the password for this share link.
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = Hash::make($password);
    }

    /**
     * Record an access to this share link.
     */
    public function recordAccess(?string $ipAddress = null, ?string $userAgent = null): DocumentShareAccess
    {
        return $this->accesses()->create([
            'accessed_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get the URL for this share link.
     */
    public function getUrl(): string
    {
        return route('shared.document', ['token' => $this->token]);
    }
}
