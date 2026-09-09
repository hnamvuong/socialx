<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => [
                'nullable',
                'string',
                'max:5000',
                'required_without:attachments',
            ],

            'attachments' => [
                'nullable',
                'array',
                'max:4',
                'required_without:body',
            ],

            'attachments.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}
