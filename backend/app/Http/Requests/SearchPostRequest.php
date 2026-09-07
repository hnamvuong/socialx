<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => [
                'required',
                'string',
                'max:100',
            ],

            'cursor' => [
                'nullable',
                'string',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $query =
            $this->query('q');

        if (is_string($query)) {
            $this->merge([
                'q' => trim($query),
            ]);
        }
    }
}
