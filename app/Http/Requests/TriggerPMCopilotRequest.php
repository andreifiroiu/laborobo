<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for triggering PM Copilot workflow.
 *
 * Validates that the request is for an existing work order.
 * The work order existence is validated via route model binding,
 * so this request primarily handles authorization and any optional parameters.
 */
class TriggerPMCopilotRequest extends FormRequest
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
            // Optional parameter to override the default PM Copilot mode
            'pm_copilot_mode' => ['sometimes', 'string', 'in:staged,full'],
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
            'pm_copilot_mode.in' => 'PM Copilot mode must be either "staged" or "full".',
        ];
    }
}
