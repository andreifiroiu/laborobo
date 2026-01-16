<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\FileUploadService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200', // 50MB in KB
                $this->blockedExtensionRule(),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.max' => 'The file size cannot exceed 50MB. Please upload a smaller file.',
        ];
    }

    /**
     * Create a custom validation rule for blocked extensions.
     */
    protected function blockedExtensionRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $value instanceof \Illuminate\Http\UploadedFile) {
                return;
            }

            $extension = strtolower($value->getClientOriginalExtension());
            $service = app(FileUploadService::class);

            if ($service->isBlockedExtension($extension)) {
                $fail("Files with the .{$extension} extension are not allowed for security reasons.");
            }
        };
    }
}
