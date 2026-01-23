<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for approving PM Copilot suggestions.
 *
 * Validates that the suggestion ID and type are provided and valid.
 */
class ApproveSuggestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the controller level.
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
            'suggestion_type' => ['required', 'string', 'in:deliverable,task'],
            'suggestion_index' => ['required', 'integer', 'min:0'],
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
            'suggestion_type.required' => 'The suggestion type is required.',
            'suggestion_type.in' => 'The suggestion type must be either "deliverable" or "task".',
            'suggestion_index.required' => 'The suggestion index is required.',
            'suggestion_index.integer' => 'The suggestion index must be a valid integer.',
            'suggestion_index.min' => 'The suggestion index must be 0 or greater.',
        ];
    }
}
