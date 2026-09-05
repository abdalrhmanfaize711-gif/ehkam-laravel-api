<?php

namespace App\Http\Requests\Api;

class UpdateStudentRecordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'from_surah' => ['nullable', 'string', 'max:100'],
            'from_ayah' => ['nullable', 'integer', 'min:1'],
            'to_surah' => ['nullable', 'string', 'max:100'],
            'to_ayah' => ['nullable', 'integer', 'min:1'],
            'repeated_times' => ['nullable', 'integer', 'min:0'],
            'memorization_state' => ['nullable', 'string', 'max:100'],
            'addition_date' => ['nullable', 'date'],
            'general_revision' => ['nullable', 'boolean'],
            'daily_revision' => ['nullable', 'boolean'],
        ];
    }
}
