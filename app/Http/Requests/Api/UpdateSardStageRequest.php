<?php

namespace App\Http\Requests\Api;

class UpdateSardStageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'sard_type' => ['required', 'string', 'max:100'],
            'memorization_state' => ['required', 'string', 'max:100'],
            'total_melodies' => ['required', 'integer', 'min:0'],
            'total_mistakes' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
            'notes_text' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
