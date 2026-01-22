<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Deliverable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Maximum file size in bytes (50MB).
     */
    public const MAX_FILE_SIZE = 52428800;

    /**
     * Default signed URL expiration in minutes.
     */
    public const DEFAULT_SIGNED_URL_EXPIRATION = 60;

    /**
     * List of blocked file extensions for security.
     */
    public const BLOCKED_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'msi', 'dll', 'scr',
        'vbs', 'vbe', 'js', 'jse', 'ws', 'wsf',
        'ps1', 'ps1xml', 'psc1', 'psd1', 'psm1',
        'sh', 'bash', 'zsh', 'csh', 'ksh',
        'app', 'dmg', 'deb', 'rpm', 'jar',
    ];

    /**
     * Validate an uploaded file for size and extension.
     *
     * @return array<int, string> Array of error messages, empty if valid
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors[] = 'The file size exceeds the maximum allowed size of 50MB.';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            $errors[] = "Files with the .{$extension} extension are not allowed for security reasons.";
        }

        return $errors;
    }

    /**
     * Check if a file extension is blocked.
     */
    public function isBlockedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::BLOCKED_EXTENSIONS, true);
    }

    /**
     * Store a document file with the standard path pattern.
     *
     * Path pattern: {team_id}/{context}/{entity_id}/{filename}
     *
     * @param string $context The storage context (e.g., 'projects', 'team-files')
     * @param string|null $disk The storage disk to use (defaults to configured document disk)
     * @return string The stored file path
     */
    public function storeDocument(
        UploadedFile $file,
        int $teamId,
        string $context,
        int $entityId,
        ?string $disk = null
    ): string {
        $disk = $disk ?? $this->getDefaultDocumentDisk();
        $originalFileName = $file->getClientOriginalName();
        $storagePath = "{$teamId}/{$context}/{$entityId}";

        return $file->storeAs($storagePath, $originalFileName, $disk);
    }

    /**
     * Store a deliverable version file.
     *
     * @param Deliverable $deliverable The deliverable model instance
     * @param string|null $disk The storage disk to use (defaults to 'public' for backward compatibility)
     * @return string The URL or path of the stored file
     */
    public function storeDeliverableVersion(
        UploadedFile $file,
        Deliverable $deliverable,
        int $versionNumber,
        ?string $disk = null
    ): string {
        $disk = $disk ?? 'public';
        $originalFileName = $file->getClientOriginalName();
        $storagePath = "deliverables/{$deliverable->id}";
        $fileName = "v{$versionNumber}_{$originalFileName}";

        $path = $file->storeAs($storagePath, $fileName, $disk);

        if ($disk === 'public') {
            return Storage::disk('public')->url($path);
        }

        return $path;
    }

    /**
     * Generate a signed URL for secure, time-limited file access.
     *
     * @param string $path The file path in storage
     * @param string|null $disk The storage disk to use
     * @param int $expirationMinutes URL expiration time in minutes
     * @return string The signed URL
     */
    public function getSignedUrl(
        string $path,
        ?string $disk = null,
        int $expirationMinutes = self::DEFAULT_SIGNED_URL_EXPIRATION
    ): string {
        $disk = $disk ?? $this->getDefaultDocumentDisk();
        $storage = Storage::disk($disk);

        if ($this->isS3Disk($disk)) {
            return $storage->temporaryUrl(
                $path,
                now()->addMinutes($expirationMinutes)
            );
        }

        return $storage->url($path);
    }

    /**
     * Generate a public URL suitable for external services like Office Online Viewer.
     *
     * @param string $path The file path in storage
     * @param string|null $disk The storage disk to use
     * @param int $expirationMinutes URL expiration time in minutes
     * @return string The public URL
     */
    public function getPublicUrl(
        string $path,
        ?string $disk = null,
        int $expirationMinutes = self::DEFAULT_SIGNED_URL_EXPIRATION
    ): string {
        return $this->getSignedUrl($path, $disk, $expirationMinutes);
    }

    /**
     * Delete a file from storage.
     *
     * @param string $fileUrl The file URL or path
     * @param string|null $disk The storage disk to use (defaults to 'public' for backward compatibility)
     */
    public function deleteFile(string $fileUrl, ?string $disk = null): bool
    {
        $disk = $disk ?? 'public';
        $storage = Storage::disk($disk);

        if ($disk === 'public') {
            $path = str_replace($storage->url(''), '', $fileUrl);
        } else {
            $path = $fileUrl;
        }

        if ($path && $storage->exists($path)) {
            return $storage->delete($path);
        }

        return false;
    }

    /**
     * Format file size in human-readable format.
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        $formatted = $unitIndex === 0 ? (int) $size : round($size, 1);

        return $formatted . ' ' . $units[$unitIndex];
    }

    /**
     * Get the default disk for document storage.
     */
    public function getDefaultDocumentDisk(): string
    {
        return config('filesystems.document_disk', 's3');
    }

    /**
     * Check if the given disk is configured as S3.
     */
    private function isS3Disk(string $disk): bool
    {
        return config("filesystems.disks.{$disk}.driver") === 's3';
    }
}
