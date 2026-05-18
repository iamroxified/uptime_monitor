<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'check_interval' => $this->input('check_interval', 5),
            'threshold' => $this->input('threshold', 3),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'url',
                Rule::unique('monitors', 'url'),
                'starts_with:http://,https://',
            ],
            'check_interval' => ['nullable', 'integer', 'min:1', 'max:60'],
            'threshold' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
