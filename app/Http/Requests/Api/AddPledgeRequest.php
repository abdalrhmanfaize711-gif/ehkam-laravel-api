<?php

namespace App\Http\Requests\Api;

class AddPledgeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'pledge_type' => ['required', 'string', 'max:100'],
            'insert_date' => ['required', 'date'],
        ];
    }
}
