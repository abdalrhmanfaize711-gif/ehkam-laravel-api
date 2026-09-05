<?php

namespace App\Http\Requests\Api;

class TeacherProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
        ];
    }
}
