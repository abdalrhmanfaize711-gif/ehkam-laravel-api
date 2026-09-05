<?php

namespace App\Http\Requests\Api;

class AddEtqanRecordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'from_surah' => ['required', 'string', 'max:100'],
            'from_ayah' => ['required', 'integer', 'min:1'],
            'to_surah' => ['required', 'string', 'max:100'],
            'to_ayah' => ['required', 'integer', 'min:1'],
            'num_of_sheets' => ['nullable', 'numeric', 'min:0'],
            'memorization_state' => ['required', 'string', 'max:100'],
            'general_revision' => ['nullable', 'boolean'],
            'addition_date' => ['required', 'date'],
            'notes_text' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
