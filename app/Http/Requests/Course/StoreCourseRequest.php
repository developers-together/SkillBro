<?php

namespace App\Http\Requests\Course;

use App\Enums\CourseLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCourseRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65535'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:9999.99'],
            'level' => ['sometimes', new Enum(CourseLevel::class)],
            'language' => ['sometimes', 'string', 'max:10'],
            'requirements' => ['sometimes', 'array'],
            'requirements.*' => ['string'],
            'what_you_learn' => ['sometimes', 'array'],
            'what_you_learn.*' => ['string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
