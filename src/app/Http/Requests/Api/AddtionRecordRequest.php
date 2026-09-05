<?php

namespace App\Http\Requests\Api;

class AddtionRecordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'records' => ['sometimes', 'array', 'min:1', 'max:500'],
            'records.*.student_id' => ['required_with:records', 'integer', 'exists:students,id'],
            'records.*.teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'records.*.from_surah' => ['nullable', 'string', 'max:100'],
            'records.*.from_ayah' => ['nullable', 'integer', 'min:1'],
            'records.*.to_surah' => ['nullable', 'string', 'max:100'],
            'records.*.to_ayah' => ['nullable', 'integer', 'min:1'],
            'records.*.memorization_state' => ['nullable', 'string', 'max:100'],
            'records.*.general_revision' => ['nullable', 'boolean'],
            'records.*.daily_revision' => ['nullable', 'boolean'],
            'records.*.repeated_times' => ['nullable', 'integer', 'min:0'],
            'records.*.addition_date' => ['nullable', 'date'],
            'records.*.notes_text' => ['nullable', 'string', 'max:2000'],

            'student_id' => ['required_without:records', 'integer', 'exists:students,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'from_surah' => ['nullable', 'string', 'max:100'],
            'from_ayah' => ['nullable', 'integer', 'min:1'],
            'to_surah' => ['nullable', 'string', 'max:100'],
            'to_ayah' => ['nullable', 'integer', 'min:1'],
            'memorization_state' => ['nullable', 'string', 'max:100'],
            'general_revision' => ['nullable', 'boolean'],
            'daily_revision' => ['nullable', 'boolean'],
            'repeated_times' => ['nullable', 'integer', 'min:0'],
            'addition_date' => ['nullable', 'date'],
            'notes_text' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
