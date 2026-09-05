<?php

namespace App\Http\Requests\Api;

class AttendanceByDateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'insert_date' => ['required', 'date'],
        ];
    }
}
