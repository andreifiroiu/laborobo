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
     * Store a deliverable version file.
     *
     * @return string The public URL of the stored file
     */
    public function storeDeliverableVersion(
        UploadedFile $file,
        Deliverable $deliverable,
        int $versionNumber
    ): string {
        $originalFileName = $file->getClientOriginalName();
        $storagePath = "deliverables/{$deliverable->id}";
        $fileName = "v{$versionNumber}_{$originalFileName}";

        $path = $file->storeAs($storagePath, $fileName, 'public');

        return Storage::disk('public')->url($path);
    }

    /**
     * Delete a file from storage.
     */
    public function deleteFile(string $fileUrl): bool
    {
        $path = str_replace(Storage::disk('public')->url(''), '', $fileUrl);

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
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
}
