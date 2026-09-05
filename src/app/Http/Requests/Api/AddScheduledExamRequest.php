<?php

namespace App\Http\Requests\Api;

class AddScheduledExamRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'num_of_juz' => ['required', 'integer', 'min:1', 'max:30'],
            'from_surah' => ['required', 'string', 'max:100'],
            'from_ayah' => ['required', 'integer', 'min:1'],
            'to_surah' => ['required', 'string', 'max:100'],
            'to_ayah' => ['required', 'integer', 'min:1'],
            'num_of_questions' => ['required', 'integer', 'min:1', 'max:200'],
            'notes_text' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
