<?php

namespace App\Http\Requests\Api;

class AddScheduledSardDayRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'sard_day' => ['required', 'integer', 'min:1'],
            'num_of_assigned_session' => ['required', 'integer', 'min:1'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'from_surah' => ['required', 'string', 'max:100'],
            'from_ayah' => ['required', 'integer', 'min:1'],
            'to_surah' => ['required', 'string', 'max:100'],
            'to_ayah' => ['required', 'integer', 'min:1'],
            'time_assigned' => ['required'],
            'sard_date' => ['required', 'date'],
            'insert_date' => ['required', 'date'],
        ];
    }
}
