<?php

namespace App\Http\Requests\Api;

class getAbsentStudentByDateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'insert_date' => ['required', 'date'],
        ];
    }
}
