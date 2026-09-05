<?php

namespace App\Http\Requests\Api;

class AddRegionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
        ];
    }
}
