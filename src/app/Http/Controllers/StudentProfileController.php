<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StudentProfileRequest;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\StudentModel;
use App\Models\AdditionRecordsModel;
use App\Models\EtqanRecordModel;
use App\Models\AttendancesModel;
use App\Models\NotsModel;
use App\Models\RecoredExamsModel;
use App\Models\SardSessionsRecordModel;
use App\Models\PledgesRecordModel;

class StudentProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET STUDENT PROFILE
    |--------------------------------------------------------------------------
    */

    public function getInfoPresnal(StudentProfileRequest $request)
    {
      
        try {

            /*
            |--------------------------------------------------------------------------
            | جلب الطالب
            |--------------------------------------------------------------------------
            */

            $student = StudentModel::with([
                'user',
                'halaqa'
            ])->findOrFail($request->student_id);


            /*
            |--------------------------------------------------------------------------
            | تحديد المرحلة
            |--------------------------------------------------------------------------
            */

            $stage = $this->normalizeStage($student->stage);


            /*
            |--------------------------------------------------------------------------
            | البيانات العامة
            |--------------------------------------------------------------------------
            */

            $data = [

                'student' => [
                    'id' => $student->id,
                    'user_id' => $student->user_id,
                    'name' => $student->user?->name,
                    'barthdate' => $student->user?->barthdate,
                    'region' => $student->user?->region,
                    'join_date' => $student->user?->join_date,
                    'stage' => $student->stage,
                    'halaqa_id' => $student->halaqa_id,
                    'tassheh_halaqa_id' => $student->tassheh_halaqa_id,
                ],

                /*
                |--------------------------------------------------------------------------
                | المرحلة
                |--------------------------------------------------------------------------
                */

                'stage' => [
                    'original' => $student->stage,
                    'type' => $stage,
                ],

                /*
                |--------------------------------------------------------------------------
                | الحضور
                |--------------------------------------------------------------------------
                */

                'attendance' => $this->getAttendance(
                    $student->user_id
                ),

                /*
                |--------------------------------------------------------------------------
                | الاختبارات
                |--------------------------------------------------------------------------
                */

                'exams' => $this->getExams(
                    $student->id
                ),

                /*
                |--------------------------------------------------------------------------
                | السرد
                |--------------------------------------------------------------------------
                */

                'sard' => $this->getSard(
                    $student->id
                ),

                /*
                |--------------------------------------------------------------------------
                | التعهدات
                |--------------------------------------------------------------------------
                */

                'pledges' => $this->getPledges(
                    $student->id
                ),

                /*
                |--------------------------------------------------------------------------
                | الملاحظات
                |--------------------------------------------------------------------------
                */

                'notes' => $this->getNotes(
                    $student->id
                ),

                /*
                |--------------------------------------------------------------------------
                | بيانات المرحلة
                |--------------------------------------------------------------------------
                */

                'addition' => null,
                'etqan' => null,
            ];


            /*
            |--------------------------------------------------------------------------
            | مرحلة الإضافة
            |--------------------------------------------------------------------------
            */

            if ($stage === 'addition') {

                $data['addition'] = $this->getAdditionProfile(
                    $student->id
                );
            }


            /*
            |--------------------------------------------------------------------------
            | مرحلة الإتقان
            |--------------------------------------------------------------------------
            */

            elseif (
                in_array($stage, [
                    'etqan_first',
                    'etqan_second',
                    'etqan_third'
                ])
            ) {

                $data['etqan'] = $this->getEtqanProfile(
                    $student->id,
                    $stage
                );
            }


            /*
            |--------------------------------------------------------------------------
            | الاستجابة
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => true,

                'message' => 'تم استرجاع ملف الطالب بنجاح.',

                'data' => $data,

            ], 200);


        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => 'فشل استرجاع ملف تعريف الطالب.',

                'error' => $e->getMessage(),

                'line' => $e->getLine(),

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE STAGE
    |--------------------------------------------------------------------------
    */

    private function normalizeStage($stage)
    {
        if ($stage === null) {
            return 'unknown';
        }

        $stage = trim((string) $stage);


        /*
        |--------------------------------------------------------------------------
        | إضافة
        |--------------------------------------------------------------------------
        */

        if (
            $stage === 'إضافة' ||
            $stage === 'اضافة' ||
            strtolower($stage) === 'addition'
        ) {
            return 'addition';
        }


        /*
        |--------------------------------------------------------------------------
        | إتقان أول
        |--------------------------------------------------------------------------
        */

        if (
            $stage === 'إتقان أول' ||
            $stage === 'اتقان أول' ||
            $stage === 'إتقان 1' ||
            $stage === 'اتقان 1' ||
            strtolower($stage) === 'etqan_first'
        ) {
            return 'etqan_first';
        }


        /*
        |--------------------------------------------------------------------------
        | إتقان ثاني
        |--------------------------------------------------------------------------
        */

        if (
            $stage === 'إتقان ثاني' ||
            $stage === 'اتقان ثاني' ||
            $stage === 'إتقان 2' ||
            $stage === 'اتقان 2' ||
            strtolower($stage) === 'etqan_second'
        ) {
            return 'etqan_second';
        }


        /*
        |--------------------------------------------------------------------------
        | إتقان ثالث
        |--------------------------------------------------------------------------
        */

        if (
            $stage === 'إتقان ثالث' ||
            $stage === 'اتقان ثالث' ||
            $stage === 'إتقان 3' ||
            $stage === 'اتقان 3' ||
            strtolower($stage) === 'etqan_third'
        ) {
            return 'etqan_third';
        }


        return 'unknown';
    }


    /*
    |--------------------------------------------------------------------------
    | NOTES
    |--------------------------------------------------------------------------
    */

    private function getNotes($studentId)
    {
        $records = NotsModel::where(
            'student_id',
            $studentId
        )
        ->orderBy('created_at', 'desc')
        ->get();


        return [

            'count' => $records->count(),

            'records' => $records,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ADDITION PROFILE
    |--------------------------------------------------------------------------
    |
    | مهم:
    | هنا يتم جلب "حفظ" فقط.
    | أي سجل "لم يحفظ" لا يتم جلبه أصلًا.
    |
    |--------------------------------------------------------------------------
    */

    private function getAdditionProfile($studentId)
    {
        /*
        |--------------------------------------------------------------------------
        | جلب سجلات الإضافة - حفظ فقط
        |--------------------------------------------------------------------------
        */

        $records = AdditionRecordsModel::where(
            'student_id',
            $studentId
        )
        ->where(
            'memorization_state',
            'حفظ'
        )
        ->orderBy(
            'addition_date',
            'asc'
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | لا توجد سجلات محفوظة
        |--------------------------------------------------------------------------
        */

        if ($records->isEmpty()) {

            return [

                'total_pages' => 0,

                'memorized_pages' => 0,

                'not_memorized_pages' => 0,

                'general_revision_pages' => 0,

                'daily_revision_pages' => 0,

                'monthly_pages' => 0,

                'total_parts' => 0,

                'records_count' => 0,

                'start_surah' => null,

                'start_ayah' => null,

                'current_surah' => null,

                'current_ayah' => null,

                'completion_percentage' => 0,

                'records' => [],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | إجمالي الصفحات
        |--------------------------------------------------------------------------
        |
        | كل السجلات هنا "حفظ"
        |
        */

        $totalPages = (float) $records->sum(
            'num_of_pages'
        );


        /*
        |--------------------------------------------------------------------------
        | الصفحات المحفوظة
        |--------------------------------------------------------------------------
        |
        | بما أن جميع السجلات حالتها "حفظ"
        | إذن المحفوظ = الإجمالي
        |
        */

        $memorizedPages = $totalPages;


        /*
        |--------------------------------------------------------------------------
        | الصفحات التي لم تحفظ
        |--------------------------------------------------------------------------
        |
        | لا يتم جلبها أصلًا
        |
        */

        $notMemorizedPages = 0;


        /*
        |--------------------------------------------------------------------------
        | الربط العام
        |--------------------------------------------------------------------------
        */

        $generalRevisionPages = (float) $records
            ->filter(function ($record) {

                return (bool) $record->general_revision === true;

            })
            ->sum('num_of_pages');


        /*
        |--------------------------------------------------------------------------
        | المراجعة اليومية
        |--------------------------------------------------------------------------
        */

        $dailyRevisionPages = (float) $records
            ->filter(function ($record) {

                return (bool) $record->daily_revision === true;

            })
            ->sum('num_of_pages');


        /*
        |--------------------------------------------------------------------------
        | أول سجل محفوظ
        |--------------------------------------------------------------------------
        */

        $firstRecord = $records->first();


        /*
        |--------------------------------------------------------------------------
        | آخر سجل محفوظ
        |--------------------------------------------------------------------------
        */

        $lastRecord = $records->last();


        /*
        |--------------------------------------------------------------------------
        | نسبة الإنجاز
        |--------------------------------------------------------------------------
        */

        $completionPercentage = round(
            min(
                ($memorizedPages / 604) * 100,
                100
            ),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | الأجزاء
        |--------------------------------------------------------------------------
        */

        $totalParts = round(
            $memorizedPages / 20,
            2
        );


        /*
        |--------------------------------------------------------------------------
        | صفحات الشهر الحالي
        |--------------------------------------------------------------------------
        |
        | يتم تمرير سجلات "حفظ" فقط
        |
        */

        $monthlyPages = $this->getCurrentMonthAdditionPages(
            $records
        );


        /*
        |--------------------------------------------------------------------------
        | النتيجة
        |--------------------------------------------------------------------------
        */

        return [

            'total_pages' => $totalPages,

            'memorized_pages' => $memorizedPages,

            'not_memorized_pages' => $notMemorizedPages,

            'general_revision_pages' => $generalRevisionPages,

            'daily_revision_pages' => $dailyRevisionPages,

            'monthly_pages' => $monthlyPages,

            'total_parts' => $totalParts,

            'records_count' => $records->count(),

            'start_surah' => $firstRecord?->from_surah,

            'start_ayah' => $firstRecord?->from_ayah,

            'current_surah' => $lastRecord?->to_surah,

            'current_ayah' => $lastRecord?->to_ayah,

            'completion_percentage' => $completionPercentage,

            'records' => $records,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ETQAN PROFILE
    |--------------------------------------------------------------------------
    |
    | مهم:
    | هنا أيضًا يتم جلب "حفظ" فقط.
    | أي سجل "لم يحفظ" لا يتم جلبه.
    |
    |--------------------------------------------------------------------------
    */

    private function getEtqanProfile($studentId, $stage)
    {
        /*
        |--------------------------------------------------------------------------
        | جلب سجلات الإتقان - حفظ فقط
        |--------------------------------------------------------------------------
        */

        $records = EtqanRecordModel::where(
            'student_id',
            $studentId
        )
        ->where(
            'memorization_state',
            'حفظ'
        )
        ->orderBy(
            'addition_date',
            'asc'
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | لا توجد سجلات محفوظة
        |--------------------------------------------------------------------------
        */

        if ($records->isEmpty()) {

            return [

                'stage' => $stage,

                'total_sheets' => 0,

                'memorized_sheets' => 0,

                'not_memorized_sheets' => 0,

                'general_revision_sheets' => 0,

                'records_count' => 0,

                'start_surah' => null,

                'start_ayah' => null,

                'current_surah' => null,

                'current_ayah' => null,

                'completion_percentage' => 0,

                'records' => [],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | إجمالي الأوجه
        |--------------------------------------------------------------------------
        |
        | كل السجلات هنا "حفظ"
        |
        */

        $totalSheets = (float) $records->sum(
            'num_of_sheets'
        );


        /*
        |--------------------------------------------------------------------------
        | الأوجه المحفوظة
        |--------------------------------------------------------------------------
        */

        $memorizedSheets = $totalSheets;


        /*
        |--------------------------------------------------------------------------
        | الأوجه التي لم تحفظ
        |--------------------------------------------------------------------------
        */

        $notMemorizedSheets = 0;


        /*
        |--------------------------------------------------------------------------
        | الربط العام
        |--------------------------------------------------------------------------
        */

        $generalRevisionSheets = (float) $records
            ->filter(function ($record) {

                return (bool) $record->general_revision === true;

            })
            ->sum('num_of_sheets');


        /*
        |--------------------------------------------------------------------------
        | أول سجل محفوظ
        |--------------------------------------------------------------------------
        */

        $firstRecord = $records->first();


        /*
        |--------------------------------------------------------------------------
        | آخر سجل محفوظ
        |--------------------------------------------------------------------------
        */

        $lastRecord = $records->last();


        /*
        |--------------------------------------------------------------------------
        | نسبة الإتقان
        |--------------------------------------------------------------------------
        */

        $completionPercentage = round(
            min(
                ($memorizedSheets / 604) * 100,
                100
            ),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | النتيجة
        |--------------------------------------------------------------------------
        */

        return [

            'stage' => $stage,

            'total_sheets' => $totalSheets,

            'memorized_sheets' => $memorizedSheets,

            'not_memorized_sheets' => $notMemorizedSheets,

            'general_revision_sheets' => $generalRevisionSheets,

            'records_count' => $records->count(),

            'start_surah' => $firstRecord?->from_surah,

            'start_ayah' => $firstRecord?->from_ayah,

            'current_surah' => $lastRecord?->to_surah,

            'current_ayah' => $lastRecord?->to_ayah,

            'completion_percentage' => $completionPercentage,

            'records' => $records,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CURRENT MONTH ADDITION PAGES
    |--------------------------------------------------------------------------
    */

    private function getCurrentMonthAdditionPages($records)
    {
        $currentMonth = now()->month;

        $currentYear = now()->year;

        $total = 0;


        foreach ($records as $record) {

            if (!$record->addition_date) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | للتأكد مرة أخرى أن الحالة حفظ
            |--------------------------------------------------------------------------
            */

            if ($record->memorization_state !== 'حفظ') {
                continue;
            }


            try {

                $date = Carbon::parse(
                    $record->addition_date
                );

            } catch (\Throwable $e) {

                continue;
            }


            if (
                $date->month == $currentMonth &&
                $date->year == $currentYear
            ) {

                $total += (float) $record->num_of_pages;
            }
        }


        return $total;
    }


    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE
    |--------------------------------------------------------------------------
    */

    private function getAttendance($userId)
    {
        /*
        |--------------------------------------------------------------------------
        | لا يوجد user
        |--------------------------------------------------------------------------
        */

        if (!$userId) {

            return [

                'total' => 0,

                'present' => 0,

                'absent' => 0,

                'late' => 0,

                'attendance_percentage' => 0,

                'records' => [],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | سجلات الحضور
        |--------------------------------------------------------------------------
        */

        $records = AttendancesModel::where(
            'user_id',
            $userId
        )
        ->orderBy(
            'insert_date',
            'desc'
        )
        ->get();


        $total = $records->count();


        /*
        |--------------------------------------------------------------------------
        | حضور
        |--------------------------------------------------------------------------
        */

        $present = $records->filter(function ($record) {

            $state = trim(
                (string) $record->attendance_state
            );

            return in_array($state, [
                'حضور',
                'حاضر',
                'present',
                'Present',
            ]);

        })->count();


        /*
        |--------------------------------------------------------------------------
        | غياب
        |--------------------------------------------------------------------------
        */

        $absent = $records->filter(function ($record) {

            $state = trim(
                (string) $record->attendance_state
            );

            return in_array($state, [
                'غياب',
                'غائب',
                'absent',
                'Absent',
            ]);

        })->count();


        /*
        |--------------------------------------------------------------------------
        | تأخر
        |--------------------------------------------------------------------------
        */

        $late = $records->filter(function ($record) {

            $state = trim(
                (string) $record->attendance_state
            );

            return in_array($state, [
                'تأخر',
                'متأخر',
                'late',
                'Late',
            ]);

        })->count();


        /*
        |--------------------------------------------------------------------------
        | النسبة
        |--------------------------------------------------------------------------
        */

        $attendancePercentage = $total > 0

            ? round(
                ($present / $total) * 100,
                2
            )

            : 0;


        return [

            'total' => $total,

            'present' => $present,

            'absent' => $absent,

            'late' => $late,

            'attendance_percentage' => $attendancePercentage,

            'records' => $records,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | EXAMS
    |--------------------------------------------------------------------------
    */

    private function getExams($studentId)
    {
        $records = RecoredExamsModel::where(
            'student_id',
            $studentId
        )
        ->orderBy(
            'insert_date',
            'desc'
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | الدرجات
        |--------------------------------------------------------------------------
        */

        $percentages = $records
            ->pluck('final_percentage')
            ->filter(function ($value) {

                return $value !== null;

            })
            ->map(function ($value) {

                return (float) $value;

            });


        return [

            'count' => $records->count(),

            'average_percentage' =>

                $percentages->count() > 0

                    ? round(
                        $percentages->avg(),
                        2
                    )

                    : 0,

            'highest_percentage' =>

                $percentages->count() > 0

                    ? $percentages->max()

                    : 0,

            'lowest_percentage' =>

                $percentages->count() > 0

                    ? $percentages->min()

                    : 0,

            'total_mistakes' =>
                (int) $records->sum(
                    'total_mistakes'
                ),

            'total_melodies' =>
                (int) $records->sum(
                    'total_melodies'
                ),

            'total_hesitiations' =>
                (int) $records->sum(
                    'total_hesitiations'
                ),

            'records' => $records,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SARD
    |--------------------------------------------------------------------------
    */

    private function getSard($studentId)
    {
        $records = SardSessionsRecordModel::where(
            'student_id',
            $studentId
        )
        ->orderBy(
            'insert_date',
            'desc'
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | إجمالي الجلسات
        |--------------------------------------------------------------------------
        */

        $totalSessions = $records->count();


        /*
        |--------------------------------------------------------------------------
        | إجمالي الأوجه المسردة
        |--------------------------------------------------------------------------
        */

        $totalSheets = (float) $records->sum(
            'num_of_sheets'
        );


        /*
        |--------------------------------------------------------------------------
        | الأخطاء
        |--------------------------------------------------------------------------
        */

        $totalMistakes = (int) $records->sum(
            'total_mistakes'
        );


        /*
        |--------------------------------------------------------------------------
        | اللحون
        |--------------------------------------------------------------------------
        */

        $totalMelodies = (int) $records->sum(
            'total_melodies'
        );


        /*
        |--------------------------------------------------------------------------
        | النتيجة
        |--------------------------------------------------------------------------
        */

        return [

            'count' => $records->count(),

            'total_sessions' => $totalSessions,

            'total_sheets' => $totalSheets,

            'total_mistakes' => $totalMistakes,

            'total_melodies' => $totalMelodies,

            'records' => $records,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | PLEDGES
    |--------------------------------------------------------------------------
    */

    private function getPledges($studentId)
    {
        /*
        |--------------------------------------------------------------------------
        | جلب التعهدات للطالب
        |--------------------------------------------------------------------------
        */

        $pledges = DB::table('pledges_record')
            ->where(
                'student_id',
                $studentId
            )
            ->orderBy(
                'insert_date',
                'desc'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | أنواع التعهدات
        |--------------------------------------------------------------------------
        */

        $systemPledges = $pledges
            ->where(
                'pledge_type',
                'تعهد نظام'
            )
            ->count();


        $absencePledges = $pledges
            ->where(
                'pledge_type',
                'تعهد غياب'
            )
            ->count();


        $additionPledges = $pledges
            ->where(
                'pledge_type',
                'تعهد إضافة'
            )
            ->count();


        $generalLinkPledges = $pledges
            ->where(
                'pledge_type',
                'تعهد ربط عام'
            )
            ->count();


        $totalPledges = $pledges->count();


        /*
        |--------------------------------------------------------------------------
        | النتيجة
        |--------------------------------------------------------------------------
        */

        return [

            'count' => $totalPledges,

            'system_pledges' => $systemPledges,

            'absence_pledges' => $absencePledges,

            'addition_pledges' => $additionPledges,

            'general_link_pledges' => $generalLinkPledges,

            'records' => $pledges,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ADDITION RECORDS API
    |--------------------------------------------------------------------------
    |
    | يرجع فقط memorization_state = حفظ
    |
    |--------------------------------------------------------------------------
    */

    public function getStudentAdditionRecords(StudentProfileRequest $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | جلب الطالب
            |--------------------------------------------------------------------------
            */

            $student = StudentModel::with([
                'user',
                'halaqa'
            ])->findOrFail(
                $request->student_id
            );


            /*
            |--------------------------------------------------------------------------
            | جلب سجلات الإضافة - حفظ فقط
            |--------------------------------------------------------------------------
            */

            $records = AdditionRecordsModel::where(
                'student_id',
                $student->id
            )
            ->where(
                'memorization_state',
                'حفظ'
            )
            ->orderBy(
                'addition_date',
                'asc'
            )
            ->get();


            /*
            |--------------------------------------------------------------------------
            | إجمالي الصفحات
            |--------------------------------------------------------------------------
            */

            $totalPages = (float) $records->sum(
                'num_of_pages'
            );


            /*
            |--------------------------------------------------------------------------
            | جميع الصفحات محفوظة
            |--------------------------------------------------------------------------
            */

            $memorizedPages = $totalPages;


            /*
            |--------------------------------------------------------------------------
            | أول سجل محفوظ
            |--------------------------------------------------------------------------
            */

            $firstRecord = $records->first();


            /*
            |--------------------------------------------------------------------------
            | آخر سجل محفوظ
            |--------------------------------------------------------------------------
            */

            $lastRecord = $records->last();


            /*
            |--------------------------------------------------------------------------
            | نسبة الإنجاز
            |--------------------------------------------------------------------------
            */

            $completionPercentage = round(
                min(
                    ($memorizedPages / 604) * 100,
                    100
                ),
                2
            );


            /*
            |--------------------------------------------------------------------------
            | الاستجابة
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => true,

                'message' =>
                    'تم استرجاع سجلات إضافة الطلاب المحفوظة بنجاح.',

                'data' => [

                    'student' => $student,

                    'total_pages' => $totalPages,

                    'memorized_pages' => $memorizedPages,

                    'not_memorized_pages' => 0,

                    'total_parts' => round(
                        $memorizedPages / 20,
                        2
                    ),

                    'completion_percentage' =>
                        $completionPercentage,

                    'start_surah' =>
                        $firstRecord?->from_surah,

                    'start_ayah' =>
                        $firstRecord?->from_ayah,

                    'current_surah' =>
                        $lastRecord?->to_surah,

                    'current_ayah' =>
                        $lastRecord?->to_ayah,

                    'records_count' =>
                        $records->count(),

                    'records' => $records,
                ],

            ], 200);


        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

            ], 500);
        }
    }
}