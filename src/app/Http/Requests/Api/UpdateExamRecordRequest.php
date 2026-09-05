<?php

namespace App\Http\Requests\Api;

class UpdateExamRecordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:record_exams,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'exam_type' => ['required', 'string', 'max:100'],
            'total_mistakes' => ['required', 'integer', 'min:0'],
            'total_melodies' => ['required', 'integer', 'min:0'],
            'total_hesitiations' => ['required', 'integer', 'min:0'],
            'final_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'num_of_questions' => ['nullable', 'integer', 'min:1', 'max:200'],
            'insert_date' => ['required', 'date'],
            'notes_text' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
