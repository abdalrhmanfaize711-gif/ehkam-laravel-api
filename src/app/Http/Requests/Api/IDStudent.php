<?php

namespace App\Http\Requests\Api;

class IDStudent extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],
        ];
    }
}