<?php

namespace App\Http\Requests\Api;

class UpdateEtqanRecordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:etqan_record,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'from_surah' => ['nullable', 'string', 'max:100'],
            'from_ayah' => ['nullable', 'integer', 'min:1'],
            'to_surah' => ['nullable', 'string', 'max:100'],
            'to_ayah' => ['nullable', 'integer', 'min:1'],
            'num_of_sheets' => ['nullable', 'numeric', 'min:0'],
            'memorization_state' => ['nullable', 'string', 'max:100'],
            'general_revision' => ['nullable', 'boolean'],
            'addition_date' => ['nullable', 'date'],
            'notes_text' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
