<?php

namespace App\Http\Requests\Api;

class AddSerdScheduleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'teacher_id' => [
                'nullable',
                'integer',
                'exists:teachers,id',
            ],

            'total_assigned_juz' => [
                'required',
                'integer',
                'min:1',
                'max:30',
            ],

            'num_of_days' => [
                'required',
                'integer',
                'min:1',
            ],

            'insert_date' => [
                'required',
                'date',
            ],

            'days' => [
                'required',
                'array',
                'min:1',
                'max:500',
            ],

            'days.*.sard_day' => [
                'required',
                'integer',
                'min:1',
            ],

            'days.*.teacher_id' => [
                'required',
                'integer',
                'exists:teachers,id',
            ],

            'days.*.num_of_sessions' => [
                'required',
                'integer',
                'min:1',
            ],

            'days.*.from_surah' => [
                'required',
                'string',
                'max:100',
            ],

            'days.*.from_ayah' => [
                'required',
                'integer',
                'min:1',
            ],

            'days.*.to_surah' => [
                'required',
                'string',
                'max:100',
            ],

            'days.*.to_ayah' => [
                'required',
                'integer',
                'min:1',
            ],

            'days.*.time_assigned' => [
                'required',
            ],

            'days.*.sard_date' => [
                'required',
                'date',
            ],
        ];
    }
}