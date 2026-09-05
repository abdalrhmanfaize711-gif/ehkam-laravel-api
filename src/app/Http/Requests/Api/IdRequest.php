<?php

namespace App\Http\Requests\Api;

class IdRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
        ];
    }
}
