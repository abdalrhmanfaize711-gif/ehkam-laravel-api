<?php

namespace App\Http\Requests\Api;

class AddLateAttendanceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'attendance_state' => ['required', 'string','max:24'],
        ];
    }
}
