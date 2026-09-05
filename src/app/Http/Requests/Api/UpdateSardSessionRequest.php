<?php

namespace App\Http\Requests\Api;

class UpdateSardSessionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'record_sard_day_id' => ['required', 'integer', 'exists:record_sard_days,id'],
            'total_mistakes' => ['required', 'integer', 'min:0'],
            'total_melodies' => ['required', 'integer', 'min:0'],
            'hesitiations_state' => ['required', 'string', 'max:100'],
            'serd_day' => ['required', 'integer', 'min:1'],
            'from_surah' => ['required', 'string', 'max:100'],
            'from_ayah' => ['required', 'integer', 'min:1'],
            'to_surah' => ['required', 'string', 'max:100'],
            'to_ayah' => ['required', 'integer', 'min:1'],
            'session_state' => ['required', 'string', 'max:100'],
            'session_number' => ['required', 'integer', 'min:1'],
            'sard_date' => ['required', 'date'],
            'insert_date' => ['nullable', 'date'],
        ];
    }
}
