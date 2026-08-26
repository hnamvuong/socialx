<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => [
                'required',
                'string',
                'max:50',
            ],

            'bio' => [
                'nullable',
                'string',
                'max:160',
            ],

            'location' => [
                'nullable',
                'string',
                'max:100',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],
        ];
    }
}
