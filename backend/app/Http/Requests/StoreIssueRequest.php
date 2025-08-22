<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Issue;

/**
 * Form Request for creating new issues
 * Handles validation rules and custom error messages
 */
class StoreIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * For now, we'll allow all requests, but in a real app you'd check permissions
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * These rules ensure data integrity and consistency
     */
    public function rules(): array
    {
        return [
            // Title must be provided and cannot exceed 255 characters
            'title' => [
                'required',
                'string', 
                'max:255',
                'min:3', // Minimum length for meaningful titles
            ],
            
            // Description is required and should provide sufficient detail
            'description' => [
                'required',
                'string',
                'min:10', // Ensure adequate description
                'max:10000', // Prevent extremely long descriptions
            ],
            
            // Status must be one of the predefined values from our model
            'status' => [
                'required',
                'string',
                'in:' . implode(',', Issue::STATUSES),
            ],
            
            // Priority must be one of the predefined values from our model  
            'priority' => [
                'required',
                'string',
                'in:' . implode(',', Issue::PRIORITIES),
            ],
        ];
    }

    /**
     * Custom error messages for validation failures
     * Provides user-friendly error messages
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for the issue.',
            'title.min' => 'The title must be at least 3 characters long.',
            'title.max' => 'The title cannot exceed 255 characters.',
            
            'description.required' => 'Please provide a description for the issue.',
            'description.min' => 'The description must be at least 10 characters long.',
            'description.max' => 'The description cannot exceed 10,000 characters.',
            
            'status.required' => 'Please select a status for the issue.',
            'status.in' => 'The selected status is invalid. Choose from: ' . implode(', ', Issue::STATUSES),
            
            'priority.required' => 'Please select a priority for the issue.',
            'priority.in' => 'The selected priority is invalid. Choose from: ' . implode(', ', Issue::PRIORITIES),
        ];
    }

    /**
     * Custom attribute names for error messages
     * Makes error messages more user-friendly
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
     * Configure the validator instance.
     * Add custom validation logic if needed
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: If priority is critical, ensure description is detailed
            if ($this->priority === 'critical' && strlen($this->description) < 50) {
                $validator->errors()->add(
                    'description', 
                    'Critical issues require a detailed description (at least 50 characters).'
                );
            }

            // Custom validation: Prevent duplicate titles for open issues
            $existingIssue = Issue::where('title', $this->title)
                                 ->where('status', '!=', 'closed')
                                 ->first();
            
            if ($existingIssue) {
                $validator->errors()->add(
                    'title',
                    'An open issue with this title already exists.'
                );
            }
        });
    }
}