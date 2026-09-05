<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordSardDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'sard_record_id' => [
                'required',
                'integer',
                'exists:sard_records,id',
            ],

            'num_of_session' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],

            'sard_day' => [
                'required',
                'integer',
            ],

            'num_of_remaining_sheets' => [
                'nullable',
                'integer',
                'min:0',
                'max:65535',
            ],
        ];
    }
}