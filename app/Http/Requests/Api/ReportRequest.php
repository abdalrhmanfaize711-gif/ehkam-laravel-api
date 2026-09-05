<?php

namespace App\Http\Requests\Api;

class ReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'stage' => ['nullable', 'string', 'max:100'],
        ];
    }
}
