<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\DeliverableVersion
 */
class DeliverableVersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fileUploadService = app(FileUploadService::class);

        return [
            'id' => $this->id,
            'version_number' => $this->version_number,
            'file_url' => $this->file_url,
            'file_name' => $this->file_name,
            'file_size' => $fileUploadService->formatFileSize($this->file_size),
            'file_size_bytes' => $this->file_size,
            'mime_type' => $this->mime_type,
            'notes' => $this->notes,
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => [
                'id' => (string) $this->uploadedBy->id,
                'name' => $this->uploadedBy->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
