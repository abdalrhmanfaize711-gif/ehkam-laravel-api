<?php

namespace App\Http\Requests\Api;

class AddHalaqaRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'halaqat' => ['sometimes', 'array', 'min:1', 'max:200'],
            'halaqat.*.max_students' => ['required_with:halaqat', 'integer', 'min:1', 'max:500'],
            'halaqat.*.teacher_id' => ['required_with:halaqat', 'integer', 'exists:teachers,id'],
            'halaqat.*.halaqa_type' => ['required_with:halaqat', 'string', 'max:100'],

            'max_students' => ['required_without:halaqat', 'integer', 'min:1', 'max:500'],
            'teacher_id' => ['required_without:halaqat', 'integer', 'exists:teachers,id'],
            'halaqa_type' => ['required_without:halaqat', 'string', 'max:100'],
        ];
    }
}
