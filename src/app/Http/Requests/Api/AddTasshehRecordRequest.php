<?php

namespace App\Http\Requests\Api;

class AddTasshehRecordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1', 'max:500'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.halaqa_id' => ['nullable', 'integer', 'exists:halaqats,id'],
            'records.*.tassheh_halaqa_id' => ['nullable', 'integer'],
            'records.*.Is_corrected' => ['required', 'boolean'],
            'records.*.insert_date' => ['required', 'date'],
        ];
    }
}
