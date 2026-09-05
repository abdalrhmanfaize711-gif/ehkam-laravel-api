<?php

namespace App\Http\Requests\Api;

class UpdateTasshehRecordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:tassheh_recoreds,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'halaqa_id' => ['nullable', 'integer', 'exists:halaqats,id'],
            'tassheh_halaqa_id' => ['nullable', 'integer'],
            'Is_corrected' => ['required', 'boolean'],
            'insert_date' => ['required', 'date'],
        ];
    }
}
