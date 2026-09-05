<?php

namespace App\Http\Requests\Api;

class DeleteStudentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
