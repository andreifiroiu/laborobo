<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CommunicationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for creating a draft client communication.
 *
 * Validates the entity type, entity ID, communication type, and optional notes.
 */
class DraftClientCommunicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the controller level via policies.
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
            'entity_type' => ['required', 'string', Rule::in(['project', 'work_order'])],
            'entity_id' => ['required', 'integer', 'min:1'],
            'communication_type' => [
                'required',
                'string',
                Rule::in(array_column(CommunicationType::cases(), 'value')),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            'entity_type.in' => 'Entity type must be either "project" or "work_order".',
            'entity_id.required' => 'An entity ID is required.',
            'entity_id.integer' => 'Entity ID must be a valid integer.',
            'communication_type.in' => 'Invalid communication type selected.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'entity_type' => 'entity type',
            'entity_id' => 'entity ID',
            'communication_type' => 'communication type',
        ];
    }
}
