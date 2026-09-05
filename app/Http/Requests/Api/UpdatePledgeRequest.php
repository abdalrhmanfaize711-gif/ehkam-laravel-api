<?php

namespace App\Http\Requests\Api;

class UpdatePledgeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:pledges_records,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'pledge_type' => ['required', 'string', 'max:100'],
            'insert_date' => ['required', 'date'],
        ];
    }
}
