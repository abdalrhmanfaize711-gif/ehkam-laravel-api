<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'barthdate' => ['required', 'date', 'before:today'],
            'region' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'min:3', 'max:100',],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'canSurpriseTestStudents' => ['required', 'boolean'],
        ];
    }
}
