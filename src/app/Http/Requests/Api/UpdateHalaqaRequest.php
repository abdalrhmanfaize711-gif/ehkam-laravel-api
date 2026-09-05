<?php

namespace App\Http\Requests\Api;

class UpdateHalaqaRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:halaqats,id'],
            'max_students' => ['required', 'integer', 'min:1', 'max:500'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'halaqa_type' => ['required', 'string', 'max:100'],
        ];
    }
}
