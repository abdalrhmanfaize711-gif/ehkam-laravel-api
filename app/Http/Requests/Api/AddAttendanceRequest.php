<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class AddAttendanceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'attendances' => ['required', 'array', 'min:1', 'max:1000'],
            'attendances.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'attendances.*.role' => ['required', 'string', Rule::in(['student', 'teacher'])],
            'attendances.*.attendance_state' => [
                'required', 'string',
                Rule::in(['present', 'absent', 'late']),
            ],
            'attendances.*.insert_date' => ['required', 'date'],
        ];
    }
}
