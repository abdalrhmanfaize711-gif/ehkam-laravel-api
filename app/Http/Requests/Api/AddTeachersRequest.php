<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class AddTeachersRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'teachers' => ['required', 'array', 'min:1', 'max:200'],
            'teachers.*.name' => ['required', 'string', 'min:2', 'max:150'],
            'teachers.*.barthdate' => ['required', 'date', 'before:today'],
            'teachers.*.join_date' => ['required', 'date'],
            'teachers.*.region' => ['required', 'string', 'max:150'],
            'teachers.*.username' => [
                'required', 'string', 'min:3', 'max:100',
                Rule::unique('teachers', 'username'),
            ],
            'teachers.*.password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
