<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
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
            // Traditional file upload (for local development)
            'program_file' => 'required_without_all:program_uris,vapor_file_key|nullable|file|mimes:pdf,txt,png,jpg,jpeg,gif,webp',
            // Vapor S3 direct upload key (for production on Vapor)
            'vapor_file_key' => 'required_without_all:program_uris,program_file|nullable|string',
            'vapor_file_name' => 'nullable|string',
            'vapor_file_type' => 'nullable|string',
            // URL input option
            'program_uris' => 'required_without_all:program_file,vapor_file_key|nullable|string',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->hasFile('program_file') && ! $validator->errors()->has('program_file')) {
                $sizeInMB = round($this->file('program_file')->getSize() / (1024 * 1024), 1);

                if ($sizeInMB > 20) {
                    $validator->errors()->add(
                        'program_file',
                        "Your file is {$sizeInMB}MB. The file size must not exceed 20MB."
                    );
                }
            }
        });
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'program_file.required_without_all' => 'Please upload a file or provide URLs.',
            'program_file.mimes' => 'The file must be a PDF, text file, or image (PNG, JPG, JPEG, GIF, WEBP).',
            'vapor_file_key.required_without_all' => 'Please upload a file or provide URLs.',
            'program_uris.required_without_all' => 'Please provide URLs or upload a file.',
        ];
    }
}
