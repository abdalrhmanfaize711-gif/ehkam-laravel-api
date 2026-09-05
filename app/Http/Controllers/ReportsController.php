<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\ReportRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\StudentModel;
use App\Models\TeacherModel;
class ReportsController extends Controller
{
  



    public function studentsReport(ReportRequest $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'from_date' => [
                'required',
                'date',
            ],

            'to_date' => [
                'required',
                'date',
                'after_or_equal:from_date',
            ],

            'stage' => [
                'required',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | جلب الطلاب حسب المرحلة
        |--------------------------------------------------------------------------
        */

        $query = DB::table('students')
            ->where(
                'students.stage',
                $request->stage
            );


        /*
        |--------------------------------------------------------------------------
        | ربط users للحصول على اسم الطالب
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('users')) {

            $query->leftJoin(
                'users',
                'users.id',
                '=',
                'students.user_id'
            );

            $query->select(
                'students.*',
                'users.name as student_name'
            );

        } else {

            $query->select(
                'students.*'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ترتيب الطلاب
        |--------------------------------------------------------------------------
        */

        $students = $query
            ->orderBy('students.id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | بناء التقرير لكل طالب
        |--------------------------------------------------------------------------
        */

        $reports = [];

        foreach ($students as $student) {

            $reports[] = $this->buildStudentReport(
                $student,
                $request->from_date,
                $request->to_date
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'filters' => [

                'from_date' =>
                    $request->from_date,

                'to_date' =>
                    $request->to_date,

                'stage' =>
                    $request->stage,
            ],

            'count' =>
                count($reports),

            'data' =>
                $reports,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | بناء تقرير الطالب
    |--------------------------------------------------------------------------
    */

    private function buildStudentReport(
        $student,
        $fromDate,
        $toDate
    ) {

        $studentId = $student->id;


        /*
        |--------------------------------------------------------------------------
        | Addition Records
        |--------------------------------------------------------------------------
        */

        $additionRecords =
            $this->getAdditionRecords(
                $studentId,
                $fromDate,
                $toDate
            );


        /*
        |--------------------------------------------------------------------------
        | Etqan Records
        |--------------------------------------------------------------------------
        */

        $etqanRecords =
            $this->getEtqanRecords(
                $studentId,
                $fromDate,
                $toDate
            );


        /*
        |--------------------------------------------------------------------------
        | حساب صفحات الإضافة
        |--------------------------------------------------------------------------
        */

        $additionPages =
            $this->calculateAdditionPages(
                $additionRecords
            );


        /*
        |--------------------------------------------------------------------------
        | حساب صفحات الإتقان
        |--------------------------------------------------------------------------
        */

        $etqanPages =
            $this->calculateEtqanPages(
                $etqanRecords
            );


        /*
        |--------------------------------------------------------------------------
        | إجمالي الصفحات
        |--------------------------------------------------------------------------
        */

        $totalPages =
            $additionPages
            +
            $etqanPages;


        /*
        |--------------------------------------------------------------------------
        | الأجزاء
        |--------------------------------------------------------------------------
        |
        | الجزء = 20 صفحة
        |
        */

        $totalParts =
            $this->pagesToParts(
                $totalPages
            );


        /*
        |--------------------------------------------------------------------------
        | أول موضع
        |--------------------------------------------------------------------------
        */

        $firstPosition =
            $this->getFirstPosition(
                $additionRecords,
                $etqanRecords
            );


        /*
        |--------------------------------------------------------------------------
        | آخر موضع
        |--------------------------------------------------------------------------
        */

        $lastPosition =
            $this->getLastPosition(
                $additionRecords,
                $etqanRecords
            );


        /*
        |--------------------------------------------------------------------------
        | الربط العام
        |--------------------------------------------------------------------------
        */

        $generalRevisionPages =
            $this->calculateGeneralRevisionPages(
                $additionRecords,
                $etqanRecords
            );


        /*
        |--------------------------------------------------------------------------
        | الحضور والغياب
        |--------------------------------------------------------------------------
        */

        $attendance =
            $this->getStudentAttendance(
                $student->user_id,
                $fromDate,
                $toDate
            );


        /*
        |--------------------------------------------------------------------------
        | Response الطالب
        |--------------------------------------------------------------------------
        */

        return [

            'student' => [

                'id' =>
                    $studentId,

                'name' =>
                    $student->student_name
                    ?? null,

                'stage' =>
                    $student->stage
                    ?? null,

                'halaqa_id' =>
                    $student->halaqa_id
                    ?? null,
            ],


            'period' => [

                'from_date' =>
                    $fromDate,

                'to_date' =>
                    $toDate,
            ],


            'memorization' => [

                'addition_pages' =>
                    round(
                        $additionPages,
                        2
                    ),

                'etqan_pages' =>
                    round(
                        $etqanPages,
                        2
                    ),

                'total_pages' =>
                    round(
                        $totalPages,
                        2
                    ),

                'total_parts' =>
                    round(
                        $totalParts,
                        2
                    ),
            ],


            'positions' => [

                'first_position' =>
                    $firstPosition,

                'last_position' =>
                    $lastPosition,
            ],


            'general_revision' => [

                'pages' =>
                    round(
                        $generalRevisionPages,
                        2
                    ),

                'parts' =>
                    round(
                        $this->pagesToParts(
                            $generalRevisionPages
                        ),
                        2
                    ),
            ],


            'attendance' => [

                'present_days' =>
                    $attendance['present_days'],

                'absent_days' =>
                    $attendance['absent_days'],
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | جلب سجلات الإضافة
    |--------------------------------------------------------------------------
    */

    private function getAdditionRecords(
        $studentId,
        $fromDate,
        $toDate
    ) {

        if (!Schema::hasTable('addition_records')) {
            return collect();
        }


        return DB::table('addition_records')
            ->where(
                'student_id',
                $studentId
            )
            ->whereDate(
                'addition_date',
                '>=',
                $fromDate
            )
            ->whereDate(
                'addition_date',
                '<=',
                $toDate
            )
            ->orderBy('addition_date')
            ->orderBy('id')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | جلب سجلات الإتقان
    |--------------------------------------------------------------------------
    */

    private function getEtqanRecords(
        $studentId,
        $fromDate,
        $toDate
    ) {

        if (!Schema::hasTable('etqan_record')) {
            return collect();
        }


        return DB::table('etqan_record')
            ->where(
                'student_id',
                $studentId
            )
            ->whereDate(
                'addition_date',
                '>=',
                $fromDate
            )
            ->whereDate(
                'addition_date',
                '<=',
                $toDate
            )
            ->orderBy('addition_date')
            ->orderBy('id')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | حساب صفحات الإضافة
    |--------------------------------------------------------------------------
    */

    private function calculateAdditionPages(
        $records
    ) {

        $pages = 0;


        foreach ($records as $record) {

            $pages += (float) (
                $record->num_of_pages
                ?? 0
            );
        }


        return $pages;
    }


    /*
    |--------------------------------------------------------------------------
    | حساب صفحات الإتقان
    |--------------------------------------------------------------------------
    */

    private function calculateEtqanPages(
        $records
    ) {

        $pages = 0;


        foreach ($records as $record) {

            $pages += (float) (
                $record->num_of_sheets
                ?? 0
            );
        }


        return $pages;
    }


    /*
    |--------------------------------------------------------------------------
    | تحويل الصفحات إلى أجزاء
    |--------------------------------------------------------------------------
    */

    private function pagesToParts(
        $pages
    ) {

        return ((float) $pages) / 20;
    }


    /*
    |--------------------------------------------------------------------------
    | أول موضع
    |--------------------------------------------------------------------------
    */

    private function getFirstPosition(
        $additionRecords,
        $etqanRecords
    ) {

        $records = collect();


        /*
        | Addition
        */

        foreach ($additionRecords as $record) {

            $records->push([

                'date' =>
                    $record->addition_date,

                'from_surah' =>
                    $record->from_surah,

                'from_ayah' =>
                    $record->from_ayah,

                'to_surah' =>
                    $record->to_surah,

                'to_ayah' =>
                    $record->to_ayah,

                'type' =>
                    'addition',

                'id' =>
                    $record->id,
            ]);
        }


        /*
        | Etqan
        */

        foreach ($etqanRecords as $record) {

            $records->push([

                'date' =>
                    $record->addition_date,

                'from_surah' =>
                    $record->from_surah,

                'from_ayah' =>
                    $record->from_ayah,

                'to_surah' =>
                    $record->to_surah,

                'to_ayah' =>
                    $record->to_ayah,

                'type' =>
                    'etqan',

                'id' =>
                    $record->id,
            ]);
        }


        if ($records->isEmpty()) {
            return null;
        }


        $first = $records
            ->sortBy([
                ['date', 'asc'],
                ['id', 'asc'],
            ])
            ->first();


        return [

            'surah' =>
                $first['from_surah'],

            'ayah' =>
                $first['from_ayah'],

            'date' =>
                $first['date'],

            'type' =>
                $first['type'],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | آخر موضع
    |--------------------------------------------------------------------------
    */

    private function getLastPosition(
        $additionRecords,
        $etqanRecords
    ) {

        $records = collect();


        /*
        | Addition
        */

        foreach ($additionRecords as $record) {

            $records->push([

                'date' =>
                    $record->addition_date,

                'from_surah' =>
                    $record->from_surah,

                'from_ayah' =>
                    $record->from_ayah,

                'to_surah' =>
                    $record->to_surah,

                'to_ayah' =>
                    $record->to_ayah,

                'type' =>
                    'addition',

                'id' =>
                    $record->id,
            ]);
        }


        /*
        | Etqan
        */

        foreach ($etqanRecords as $record) {

            $records->push([

                'date' =>
                    $record->addition_date,

                'from_surah' =>
                    $record->from_surah,

                'from_ayah' =>
                    $record->from_ayah,

                'to_surah' =>
                    $record->to_surah,

                'to_ayah' =>
                    $record->to_ayah,

                'type' =>
                    'etqan',

                'id' =>
                    $record->id,
            ]);
        }


        if ($records->isEmpty()) {
            return null;
        }


        $last = $records
            ->sortBy([
                ['date', 'desc'],
                ['id', 'desc'],
            ])
            ->first();


        return [

            'surah' =>
                $last['to_surah'],

            'ayah' =>
                $last['to_ayah'],

            'date' =>
                $last['date'],

            'type' =>
                $last['type'],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | حساب الربط العام
    |--------------------------------------------------------------------------
    */

    private function calculateGeneralRevisionPages(
        $additionRecords,
        $etqanRecords
    ) {

        $pages = 0;


        /*
        | Addition
        */

        foreach ($additionRecords as $record) {

            if (
                (bool) (
                    $record->general_revision
                    ?? false
                )
            ) {

                $pages += (float) (
                    $record->num_of_pages
                    ?? 0
                );
            }
        }


        /*
        | Etqan
        */

        foreach ($etqanRecords as $record) {

            if (
                (bool) (
                    $record->general_revision
                    ?? false
                )
            ) {

                $pages += (float) (
                    $record->num_of_sheets
                    ?? 0
                );
            }
        }


        return $pages;
    }


    /*
    |--------------------------------------------------------------------------
    | حضور وغياب الطالب
    |--------------------------------------------------------------------------
    */

   private function getStudentAttendance($studentId, $fromDate, $toDate)
{
    if (!Schema::hasTable('attendances')) {
        return [
            'present_days' => 0,
            'absent_days' => 0,
        ];
    }

    $student = StudentModel::find($studentId);

    if (!$student) {
        return [
            'present_days' => 0,
            'absent_days' => 0,
        ];
    }

    $records = DB::table('attendances')
        ->where('user_id', $student->user_id)
        ->whereBetween('insert_date', [
            $fromDate,
            $toDate
        ])
        ->get([
            'insert_date',
            'attendance_state'
        ]);

    $presentDates = [];
    $absentDates = [];

    foreach ($records as $record) {

        $date = $record->insert_date;

        switch ($record->attendance_state) {

            case 'present':
            case 'late':
                $presentDates[$date] = true;
                break;

            case 'absent':
                $absentDates[$date] = true;
                break;
        }
    }

    return [
        'present_days' => count($presentDates),
        'absent_days' => count($absentDates),
    ];
}
  

    private function buildTeacherReport(
        $teacher,
        $fromDate,
        $toDate
    ) {

        $teacherId =
            $teacher->id;


        /*
        |--------------------------------------------------------------------------
        | حضور المعلم
        |--------------------------------------------------------------------------
        */

        $attendance =
            $this->getTeacherAttendance(
                $teacherId,
                $fromDate,
                $toDate
            );


        /*
        |--------------------------------------------------------------------------
        | عدد الاختبارات التي عملها المعلم
        |--------------------------------------------------------------------------
        */

        $examCount =
            $this->getTeacherExamCount(
                $teacherId,
                $fromDate,
                $toDate
            );


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [

            'teacher' => [

                'id' =>
                    $teacherId,

                'name' =>
                    $teacher->teacher_name
                    ?? null,
            ],


            'period' => [

                'from_date' =>
                    $fromDate,

                'to_date' =>
                    $toDate,
            ],


            'attendance' => [

                'present_days' =>
                    $attendance['present_days'],

                'absent_days' =>
                    $attendance['absent_days'],
            ],


            'exams' => [

                'total_exams' =>
                    $examCount,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | حضور المعلم
    |--------------------------------------------------------------------------
    */

  private function getTeacherAttendance($teacherId, $fromDate, $toDate)
{
    if (!Schema::hasTable('attendances')) {
        return [
            'present_days' => 0,
            'absent_days' => 0,
        ];
    }

    $teacher = TeacherModel::find($teacherId);

    if (!$teacher) {
        return [
            'present_days' => 0,
            'absent_days' => 0,
        ];
    }

    $records = DB::table('attendances')
        ->where('user_id', $teacher->user_id)
        ->where('role', 'teacher')
        ->whereBetween('insert_date', [
            $fromDate,
            $toDate
        ])
        ->get([
            'insert_date',
            'attendance_state'
        ]);

    $presentDates = [];
    $absentDates = [];

    foreach ($records as $record) {

        switch ($record->attendance_state) {

            case 'present':
            case 'late':
                $presentDates[$record->insert_date] = true;
                break;

            case 'absent':
                $absentDates[$record->insert_date] = true;
                break;
        }
    }

    return [
        'present_days' => count($presentDates),
        'absent_days' => count($absentDates),
    ];
}

    private function getTeacherExamCount(
        $teacherId,
        $fromDate,
        $toDate
    ) {

        if (!Schema::hasTable('record_exam')) {

            return 0;
        }


        /*
        |--------------------------------------------------------------------------
        | teacher_id
        |--------------------------------------------------------------------------
        */

        if (
            !Schema::hasColumn(
                'record_exam',
                'teacher_id'
            )
        ) {

            return 0;
        }


        /*
        |--------------------------------------------------------------------------
        | البحث عن عمود التاريخ
        |--------------------------------------------------------------------------
        */

        $dateColumn = $this->findColumn(
            'record_exam',
            [
                'exam_date',
                'date',
                'insert_date',
                'created_at',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | إذا لم يوجد تاريخ
        |--------------------------------------------------------------------------
        */

        if (!$dateColumn) {

            return 0;
        }


        /*
        |--------------------------------------------------------------------------
        | العدد
        |--------------------------------------------------------------------------
        */

        return DB::table('record_exam')
            ->where(
                'teacher_id',
                $teacherId
            )
            ->whereDate(
                $dateColumn,
                '>=',
                $fromDate
            )
            ->whereDate(
                $dateColumn,
                '<=',
                $toDate
            )
            ->count();
    }


    /*
    |--------------------------------------------------------------------------
    | البحث عن عمود موجود
    |--------------------------------------------------------------------------
    */

    private function findColumn(
        $table,
        array $columns
    ) {

        foreach ($columns as $column) {

            if (
                Schema::hasColumn(
                    $table,
                    $column
                )
            ) {

                return $column;
            }
        }


        return null;
    }
}