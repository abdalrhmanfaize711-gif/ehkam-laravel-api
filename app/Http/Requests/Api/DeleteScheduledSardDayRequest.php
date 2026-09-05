<?php

namespace App\Http\Requests\Api;

class DeleteScheduledSardDayRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
        ];
    }
}
