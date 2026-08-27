<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post
            && $this->user()
            && $this->user()->id === $post->user_id;
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
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Post|null $post */
                $post = $this->route('post');

                if (! $post instanceof Post) {
                    return;
                }

                $content = $this->input(
                    'content'
                );

                $hasContent =
                    is_string($content)
                    && trim($content) !== '';

                $hasMedia =
                    $post->media()->exists();

                if (
                    ! $hasContent
                    && ! $hasMedia
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'content',
                            'Bài viết phải có nội dung hoặc hình ảnh.'
                        );
                }
            },
        ];
    }
}
