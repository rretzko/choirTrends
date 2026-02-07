<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StoreFounderProgramRequest extends StoreProgramRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isFounder();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'target_user_id' => 'required|exists:users,id',
        ]);
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'target_user_id.required' => 'Please select a user.',
            'target_user_id.exists' => 'The selected user does not exist.',
        ]);
    }
}
