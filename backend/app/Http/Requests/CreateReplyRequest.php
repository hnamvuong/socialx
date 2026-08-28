<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class CreateReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $content = $this->input('content');

        if (! is_string($content)) {
            return;
        }

        $content = trim($content);

        $this->merge([
            'content' => $content !== ''
                ? $content
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'content' => [
                'nullable',
                'string',
                'max:280',
            ],

            'media' => [
                'nullable',
                'array',
                'max:4',
            ],

            'media.*' => [
                File::image()
                    ->max('8mb'),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $content =
                    $this->input('content');

                $files =
                    $this->file(
                        'media',
                        []
                    );

                $hasContent =
                    is_string($content)
                    && trim($content) !== '';

                $hasMedia =
                    is_array($files)
                    && count($files) > 0;

                if (
                    ! $hasContent
                    && ! $hasMedia
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'content',
                            'Phản hồi phải có nội dung hoặc hình ảnh.'
                        );
                }
            },
        ];
    }
}
