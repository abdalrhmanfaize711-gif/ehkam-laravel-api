<?php

namespace App\Http\Requests\Api;

class AddStudentsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'students' => ['required', 'array', 'min:1', 'max:500'],
            'students.*.name' => ['required', 'string', 'min:2', 'max:150'],
            'students.*.barthdate' => ['required', 'date', 'before:today'],
            'students.*.region' => ['required', 'string', 'max:150'],
            'students.*.join_date' => ['required', 'date'],
            'students.*.halaqa_id' => ['required', 'integer', 'exists:halaqats,id'],
            'students.*.tassheh_halaqa_id' => ['nullable', 'integer'],
            'students.*.stage' => ['required', 'string', 'max:100'],
        ];
    }
}
