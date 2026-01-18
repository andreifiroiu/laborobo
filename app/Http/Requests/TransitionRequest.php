<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use App\Enums\WorkOrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class TransitionRequest extends FormRequest
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
        $statusRule = $this->determineStatusRule();

        $rules = [
            'status' => ['required', 'string', $statusRule],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];

        // Require comment when status is revision_requested
        if ($this->input('status') === 'revision_requested') {
            $rules['comment'] = ['required', 'string', 'min:10', 'max:2000'];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'A target status is required.',
            'status.in' => 'The selected status is invalid.',
            'comment.required' => 'A comment is required when requesting revisions.',
            'comment.min' => 'The comment must be at least 10 characters.',
            'comment.max' => 'The comment must not exceed 2000 characters.',
        ];
    }

    /**
     * Determine the validation rule for status based on the route.
     */
    private function determineStatusRule(): In
    {
        // Check which type of model we're transitioning based on route
        $routeName = $this->route()?->getName() ?? '';

        if (str_contains($routeName, 'work-orders')) {
            return Rule::in(array_column(WorkOrderStatus::cases(), 'value'));
        }

        // Default to TaskStatus
        return Rule::in(array_column(TaskStatus::cases(), 'value'));
    }
}
