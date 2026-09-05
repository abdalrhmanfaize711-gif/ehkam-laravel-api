<?php

namespace App\Http\Requests\Api;

class UpdateRecordPlanYearsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:record_plan_years,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'start_date' => ['required', 'date'],
            'min_juz_target' => ['required', 'integer', 'min:1', 'max:30'],
            'min_from_surah' => ['required', 'string', 'max:100'],
            'min_from_ayah' => ['required', 'integer', 'min:1'],
            'min_to_surah' => ['required', 'string', 'max:100'],
            'min_to_ayah' => ['required', 'integer', 'min:1'],
            'ideal_juz_target' => ['required', 'integer', 'min:1', 'max:30'],
            'ideal_from_surah' => ['required', 'string', 'max:100'],
            'ideal_from_ayah' => ['required', 'integer', 'min:1'],
            'ideal_to_surah' => ['required', 'string', 'max:100'],
            'ideal_to_ayah' => ['required', 'integer', 'min:1'],
            'insert_date' => ['required', 'date'],
        ];
    }
}
