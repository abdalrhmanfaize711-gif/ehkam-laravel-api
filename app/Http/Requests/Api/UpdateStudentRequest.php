<?php

namespace App\Http\Requests\Api;

class UpdateStudentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'students' => ['sometimes', 'array', 'min:1', 'max:500'],

            'students.*.user_id' => ['required_with:students', 'integer', 'exists:users,id'],
            'students.*.name' => ['required_with:students', 'string', 'min:2', 'max:150'],
            'students.*.barthdate' => ['required_with:students', 'date', 'before:today'],
            'students.*.region' => ['required_with:students', 'string', 'max:150'],
            'students.*.join_date' => ['required_with:students', 'date'],
            'students.*.halaqa_id' => ['required_with:students', 'integer', 'exists:halaqats,id'],
            'students.*.tassheh_halaqa_id' => ['nullable', 'integer'],
            'students.*.stage' => ['required_with:students', 'string', 'max:100'],

            'user_id' => ['required_without:students', 'integer', 'exists:users,id'],
            'name' => ['required_without:students', 'string', 'min:2', 'max:150'],
            'barthdate' => ['required_without:students', 'date', 'before:today'],
            'region' => ['required_without:students', 'string', 'max:150'],
            'join_date' => ['required_without:students', 'date'],
            'halaqa_id' => ['required_without:students', 'integer', 'exists:halaqats,id'],
            'tassheh_halaqa_id' => ['nullable', 'integer'],
            'stage' => ['required_without:students', 'string', 'max:100'],
        ];
    }
}
