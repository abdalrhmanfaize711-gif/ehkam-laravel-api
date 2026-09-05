<?php

namespace App\Http\Requests\Api;

class LoginStudentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'id' => ['required', 'integer', 'exists:students,id'],
        ];
    }
}
