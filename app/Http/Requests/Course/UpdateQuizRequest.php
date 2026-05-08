<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
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
            'questions' => ['sometimes', 'array', 'min:1'],
            'questions.*.question' => ['required_with:questions', 'string', 'max:5000'],
            'questions.*.position' => ['sometimes', 'integer', 'min:1'],
            'questions.*.answers' => ['required_with:questions', 'array', 'min:2'],
            'questions.*.answers.*.answer' => ['required_with:questions', 'string', 'max:255'],
            'questions.*.answers.*.is_correct' => ['required_with:questions', 'boolean'],
        ];
    }
}
