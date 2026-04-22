<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If director_name is empty, use the authenticated user's name
        if (empty($this->director_name)) {
            $this->merge([
                'director_name' => $this->user()->name,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_name' => 'required|string|max:255',
            'event_date' => 'required|date',
            'school_name' => 'required|string|max:255',
            'director_name' => 'required|string|max:255',
            'ensembles' => 'nullable|array',
            'ensembles.*.name' => 'nullable|string|max:255',
            'ensembles.*.director' => 'nullable|string|max:255',
            'ensembles.*.songs' => 'nullable|array',
            'ensembles.*.songs.*.title' => 'nullable|string|max:255',
            'ensembles.*.songs.*.composer' => 'nullable|string|max:255',
            'ensembles.*.songs.*.arranger' => 'nullable|string|max:255',
            'ensembles.*.songs.*.notes' => 'nullable|string|max:10000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_name.required' => 'The event name is required.',
            'event_date.required' => 'The event date is required.',
            'event_date.date' => 'The event date must be a valid date.',
            'school_name.required' => 'The school name is required.',
            'director_name.required' => 'The director name is required.',
        ];
    }
}
