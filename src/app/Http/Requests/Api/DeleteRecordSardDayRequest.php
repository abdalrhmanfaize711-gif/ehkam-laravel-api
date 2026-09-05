<?php

namespace App\Http\Requests\Api;

class DeleteRecordSardDayRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
        ];
    }
}
