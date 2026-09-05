<?php

namespace App\Http\Requests\Api;

class AddSardStageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'sard_type' => ['required', 'string', 'max:100'],
            'sard_duration' => ['required', 'integer', 'min:1'],
            'memorization_state' => ['required', 'string', 'max:100'],
            'total_mistakes' => ['nullable', 'integer', 'min:0'],
            'total_melodies' => ['nullable', 'integer', 'min:0'],
            'hesitation_state' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
            'notes_text' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
