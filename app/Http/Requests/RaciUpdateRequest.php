<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RaciUpdateRequest extends FormRequest
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
            'accountable_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'responsible_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'reviewer_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'consulted_ids' => ['sometimes', 'nullable', 'array'],
            'consulted_ids.*' => ['integer', 'exists:users,id'],
            'informed_ids' => ['sometimes', 'nullable', 'array'],
            'informed_ids.*' => ['integer', 'exists:users,id'],
            'confirmed' => ['sometimes', 'boolean'],
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
            'accountable_id.required' => 'An accountable user is required.',
            'accountable_id.exists' => 'The selected accountable user does not exist.',
            'responsible_id.exists' => 'The selected responsible user does not exist.',
            'reviewer_id.exists' => 'The selected reviewer does not exist.',
            'consulted_ids.array' => 'Consulted users must be provided as an array.',
            'consulted_ids.*.exists' => 'One or more consulted users do not exist.',
            'informed_ids.array' => 'Informed users must be provided as an array.',
            'informed_ids.*.exists' => 'One or more informed users do not exist.',
        ];
    }
}
