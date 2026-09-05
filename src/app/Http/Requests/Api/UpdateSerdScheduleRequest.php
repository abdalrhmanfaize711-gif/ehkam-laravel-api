<?php

namespace App\Http\Requests\Api;

class UpdateSerdScheduleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:serd_schedules,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'total_assigned_juz' => ['required', 'integer', 'min:1', 'max:30'],
            'num_of_days' => ['required', 'integer', 'min:1'],
            'insert_date' => ['nullable', 'date'],
            'notes_text' => ['nullable', 'string', 'max:2000'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ];
    }
}
