<?php

namespace App\Http\Requests\Api;

class UpdateRegionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:region,id'],
            'name' => ['required', 'string', 'min:2', 'max:150'],
        ];
    }
}
