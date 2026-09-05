<?php

namespace App\Http\Requests\Api;

class UpdateScheduledSardStageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'sard_type' => ['required', 'string', 'max:100'],
            'sard_date' => ['required', 'date'],
            'insert_date' => ['required', 'date'],
        ];
    }
}
