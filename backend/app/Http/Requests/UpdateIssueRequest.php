<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Issue;

/**
 * Form Request for updating existing issues
 * Handles validation rules for partial updates (PATCH requests)
 */
class UpdateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Issue;

/**
 * Form Request for updating existing issues
 * Handles validation rules for partial updates (PATCH requests)
 */
class UpdateIssueRequest extends FormRequest
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
     * All fields are optional for PATCH updates (partial updates)
     */
    public function rules(): array
    {
        return [
            // Title is optional for updates, but if provided must meet requirements
            'title' => [
                'sometimes', // Only validate if present in request
                'string',
                'max:255',
                'min:3',
            ],
            
            // Description is optional for updates
            'description' => [
                'sometimes',
                'string', 
                'min:10',
                'max:10000',
            ],
            
            // Status is optional but must be valid if provided
            'status' => [
                'sometimes',
                'string',
                'in:' . implode(',', Issue::STATUSES),
            ],
            
            // Priority is optional but must be valid if provided
            'priority' => [
                'sometimes', 
                'string',
                'in:' . implode(',', Issue::PRIORITIES),
            ],
        ];
    }

    /**
     * Custom error messages for validation failures
     */
    public function messages(): array
    {
        return [
            'title.min' => 'The title must be at least 3 characters long.',
            'title.max' => 'The title cannot exceed 255 characters.',
            
            'description.min' => 'The description must be at least 10 characters long.',
            'description.max' => 'The description cannot exceed 10,000 characters.',
            
            'status.in' => 'The selected status is invalid. Choose from: ' . implode(', ', Issue::STATUSES),
            'priority.in' => 'The selected priority is invalid. Choose from: ' . implode(', ', Issue::PRIORITIES),
        ];
    }

    /**
     * Custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'title' => 'issue title',
            'description' => 'issue description', 
            'status' => 'issue status',
            'priority' => 'issue priority',
        ];
    }

    /**
     * Configure the validator instance for custom validation logic
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Get the issue being updated
            $issue = $this->route('issue');
            
            if (!$issue) {
                return;
            }

            // Custom validation: Prevent closing critical issues without review
            if ($this->has('status') && $this->status === 'closed' && $issue->priority === 'critical') {
                // In a real app, you might check if user has admin role
                $validator->errors()->add(
                    'status',
                    'Critical issues require admin approval before closing.'
                );
            }

            // Custom validation: If updating to critical priority, ensure detailed description
            if ($this->has('priority') && $this->priority === 'critical') {
                $description = $this->description ?? $issue->description;
                if (strlen($description) < 50) {
                    $validator->errors()->add(
                        'priority',
                        'Critical priority requires a detailed description (at least 50 characters).'
                    );
                }
            }

            // Custom validation: Prevent duplicate titles when updating title
            if ($this->has('title')) {
                $existingIssue = Issue::where('title', $this->title)
                                     ->where('id', '!=', $issue->id)
                                     ->where('status', '!=', 'closed')
                                     ->first();
                
                if ($existingIssue) {
                    $validator->errors()->add(
                        'title',
                        'Another open issue with this title already exists.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     * Clean and format input data before validation
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string fields
        if ($this->has('title')) {
            $this->merge(['title' => trim($this->title)]);
        }

        if ($this->has('description')) {
            $this->merge(['description' => trim($this->description)]);
        }

        // Normalize status and priority to lowercase  
        if ($this->has('status')) {
            $this->merge(['status' => strtolower($this->status)]);
        }

        if ($this->has('priority')) {
            $this->merge(['priority' => strtolower($this->priority)]);
        }
    }
}
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * All fields are optional for PATCH updates (partial updates)
     */
    public function rules(): array
    {
        return [
            // Title is optional for updates, but if provided must meet requirements
            'title' => [
                'sometimes', // Only validate if present in request
                'string',
                'max:255',
                'min:3',
            ],
            
            // Description is optional for updates
            'description' => [
                'sometimes',
                'string', 
                'min:10',
                'max:10000',
            ],
            
            // Status is optional but must be valid if provided
            'status' => [
                'sometimes',
                'string',
                'in:' . implode(',', Issue::STATUSES),
            ],
            
            // Priority is optional but must be valid if provided
            'priority' => [
                'sometimes', 
                'string',
                'in:' . implode(',', Issue::PRIORITIES),
            ],
        ];
    }

    /**
     * Custom error messages for validation failures
     */
    public function messages(): array
    {
        return [
            'title.min' => 'The title must be at least 3 characters long.',
            'title.max' => 'The title cannot exceed 255 characters.',
            
            'description.min' => 'The description must be at least 10 characters long.',
            'description.max' => 'The description cannot exceed 10,000 characters.',
            
            'status.in' => 'The selected status is invalid. Choose from: ' . implode(', ', Issue::STATUSES),
            'priority.in' => 'The selected priority is invalid. Choose from: ' . implode(', ', Issue::PRIORITIES),
        ];
    }

    /**
     * Custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'title' => 'issue title',
            'description' => 'issue description', 
            'status' => 'issue status',
            'priority' => 'issue priority',
        ];
    }

    /**
     * Configure the validator instance for custom validation logic
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Get the issue being updated
            $issue = $this->route('issue');
            
            if (!$issue) {
                return;
            }

            // Custom validation: Prevent closing critical issues without review
            if ($this->has('status') && $this->status === 'closed' && $issue->priority === 'critical') {
                // In a real app, you might check if user has admin role
                $validator->errors()->add(
                    'status',
                    'Critical issues require admin approval before closing.'
                );
            }

            // Custom validation: If updating to critical priority, ensure detailed description
            if ($this->has('priority') && $this->priority === 'critical') {
                $description = $this->description ?? $issue->description;
                if (strlen($description) < 50) {
                    $validator->errors()->add(
                        'priority',
                        'Critical priority requires a detailed description (at least 50 characters).'
                    );
                }
            }

            // Custom validation: Prevent duplicate titles when updating title
            if ($this->has('title')) {
                $existingIssue = Issue::where('title', $this->title)
                                     ->where('id', '!=', $issue->id)
                                     ->where('status', '!=', 'closed')
                                     ->first();
                
                if ($existingIssue) {
                    $validator->errors()->add(
                        'title',
                        'Another open issue with this title already exists.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     * Clean and format input data before validation
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string fields
        if ($this->has('title')) {
            $this->merge(['title' => trim($this->title)]);
        }

        if ($this->has('description')) {
            $this->merge(['description' => trim($this->description)]);
        }

        // Normalize status and priority to lowercase  
        if ($this->has('status')) {
            $this->merge(['status' => strtolower($this->status)]);
        }

        if ($this->has('priority')) {
            $this->merge(['priority' => strtolower($this->priority)]);
        }
    }
}