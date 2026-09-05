<?php

namespace App\Http\Requests\Api;

class AddStudentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'barthdate' => ['required', 'date', 'before:today'],
            'region' => ['required', 'string', 'max:150'],
            'join_date' => ['required', 'date'],
            'halaqa_id' => ['required', 'integer', 'exists:halaqats,id'],
            'tassheh_halaqa_id' => ['nullable', 'integer'],
            'stage' => ['required', 'string', 'max:100'],
        ];
    }
}
