<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class AddTeacherRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'barthdate' => ['required', 'date', 'before:today'],
            'join_date' => ['required', 'date'],
            'region' => ['required', 'string', 'max:150'],
            'username' => [
                'required', 'string', 'min:3', 'max:100',
                Rule::unique('teachers', 'username'),
            ],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
