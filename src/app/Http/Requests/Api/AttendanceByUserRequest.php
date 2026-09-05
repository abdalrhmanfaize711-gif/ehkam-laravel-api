<?php

namespace App\Http\Requests\Api;

class AttendanceByUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
