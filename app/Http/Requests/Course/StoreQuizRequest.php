<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
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
            'pass_percentage' => ['sometimes', 'integer', 'between:1,100'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string', 'max:5000'],
            'questions.*.position' => ['sometimes', 'integer', 'min:1'],
            'questions.*.answers' => ['required', 'array', 'min:2'],
            'questions.*.answers.*.answer' => ['required', 'string', 'max:255'],
            'questions.*.answers.*.is_correct' => ['required', 'boolean'],
        ];
    }
}
