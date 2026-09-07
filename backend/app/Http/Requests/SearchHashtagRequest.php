<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SearchHashtagRequest extends FormRequest
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $query =
            $this->query('q');

        if (! is_string($query)) {
            return;
        }

        $query =
            trim($query);

        if (
            Str::startsWith(
                $query,
                '#'
            )
        ) {
            $query =
                Str::after(
                    $query,
                    '#'
                );
        }

        $this->merge([
            'q' => Str::lower(
                trim($query)
            ),
        ]);
    }
}
