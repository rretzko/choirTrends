<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'program_file' => 'required_without:program_uris|nullable|file|mimes:pdf,txt,png,jpg,jpeg,gif,webp|max:102400',
            'program_uris' => 'required_without:program_file|nullable|string',
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
            'program_file.required_without' => 'Please upload a file or provide URLs.',
            'program_file.mimes' => 'The file must be a PDF, text file, or image (PNG, JPG, JPEG, GIF, WEBP).',
            'program_file.max' => 'The file size must not exceed 100MB.',
            'program_uris.required_without' => 'Please provide URLs or upload a file.',
        ];
    }
}
