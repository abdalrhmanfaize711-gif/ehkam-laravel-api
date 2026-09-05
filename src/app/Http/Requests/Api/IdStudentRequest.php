<?php

namespace App\Http\Requests\Api;

class IdStudentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer'],
        ];
    }
}
