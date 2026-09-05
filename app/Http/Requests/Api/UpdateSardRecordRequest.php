<?php

namespace App\Http\Requests\Api;

class UpdateSardRecordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:sard_records,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'total_assigned_juz' => ['required', 'integer', 'min:1', 'max:30'],
            'num_of_days' => ['required', 'integer', 'min:1'],
            'insert_date' => ['required', 'date'],
        ];
    }
}
