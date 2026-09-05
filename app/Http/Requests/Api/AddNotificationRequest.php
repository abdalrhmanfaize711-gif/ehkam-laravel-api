<?php

namespace App\Http\Requests\Api;

class AddNotificationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'halaqa_id' => ['nullable', 'integer', 'exists:halaqats,id'],
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'notification_time' => ['required', 'date_format:H:i:s'],
            'insert_date' => ['required', 'date'],
        ];
    }
}
