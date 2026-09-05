<?php

namespace App\Http\Requests\Api;

class DeleteHalaqaRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:halaqats,id'],
        ];
    }
}
