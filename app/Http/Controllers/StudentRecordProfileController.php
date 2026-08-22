<?php

namespace App\Http\Controllers;

use App\Models\StudentModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MohamedSabil83\LaravelHijrian\Facades\Hijrian;

class StudentRecordProfileController extends Controller
{
    /**
     * Student Record Profile
     *
     * يرجع البيانات المطلوبة لواجهة:
     * الملف الشخصي للطالب
     */
    public function getStudentRecordProfile(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
        ]);

        $studentId = $validated['student_id'];

        /*
        |--------------------------------------------------------------------------
        | 2. Student
        |--------------------------------------------------------------------------
        */

        $student = StudentModel::findOrFail($studentId);

        /*
        |--------------------------------------------------------------------------
        | 3. User
        |--------------------------------------------------------------------------
        */

        $user = DB::table('users')
            ->where('id', $student->user_id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم المرتبط بالطالب غير موجود',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Halaqa
        |--------------------------------------------------------------------------
        */

        $halaqa = null;

        if ($student->halaqa_id) {
            $halaqa = DB::table('halaqats')
                ->where('id', $student->halaqa_id)
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Determine Student Stage
        |--------------------------------------------------------------------------
        |
        | إضافة
        | إتقان أول
        | إتقان ثاني
        | إتقان ثالث
        |
        */

        $stage = $student->stage ?? '';

        $isEtqan = mb_strpos($stage, 'إتقان') !== false;

        $recordType = $isEtqan
            ? 'etqan'
            : 'addition';

        /*
        |--------------------------------------------------------------------------
        | 6. Get records
        |--------------------------------------------------------------------------
        */

        $tableName = $isEtqan
            ? 'etqan_record'
            : 'addition_records';

        $records = DB::table($tableName)
            ->where('student_id', $studentId)
            ->orderBy('addition_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 7. First / Last Record
        |--------------------------------------------------------------------------
        */

        $firstRecord = $records->first();
        $lastRecord = $records->last();

        /*
        |--------------------------------------------------------------------------
        | 8. Memorization Start
        |--------------------------------------------------------------------------
        */

        $memorizationStart = null;

        if ($firstRecord) {
            $memorizationStart = [
                'surah' => $firstRecord->from_surah,
                'ayah' => $firstRecord->from_ayah,

                'display' =>
                    $firstRecord->from_surah .
                    ' - الآية ' .
                    $firstRecord->from_ayah,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Current Surah
        |--------------------------------------------------------------------------
        */

        $currentSurah = null;

        if ($lastRecord) {
            $currentSurah = [
                'surah' => $lastRecord->to_surah,
                'ayah' => $lastRecord->to_ayah,

                'display' =>
                    $lastRecord->to_surah .
                    ' - الآية ' .
                    $lastRecord->to_ayah,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Last Recitation
        |--------------------------------------------------------------------------
        */

        $lastRecitation = null;

        if ($lastRecord) {
            $lastRecitation = [
                'from' => [
                    'surah' => $lastRecord->from_surah,
                    'ayah' => $lastRecord->from_ayah,
                ],

                'to' => [
                    'surah' => $lastRecord->to_surah,
                    'ayah' => $lastRecord->to_ayah,
                ],

                'date' => $lastRecord->addition_date,

                'display' =>
                    'من سورة ' .
                    $lastRecord->from_surah .
                    ' آية ' .
                    $lastRecord->from_ayah .
                    ' إلى سورة ' .
                    $lastRecord->to_surah .
                    ' آية ' .
                    $lastRecord->to_ayah,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Memorization State
        |--------------------------------------------------------------------------
        */

        $memorizationState = $lastRecord->memorization_state ?? null;

        /*
        |--------------------------------------------------------------------------
        | 12. Revision Statistics
        |--------------------------------------------------------------------------
        */

        $generalDone = 0;
        $generalNotDone = 0;
        $generalPercentage = 0;

        $dailyDone = 0;
        $dailyNotDone = 0;
        $dailyPercentage = 0;

        if ($records->count() > 0) {

            /*
            | الربط العام
            */

            $generalDone = $records
                ->where('general_revision', true)
                ->count();

            $generalNotDone =
                $records->count() - $generalDone;

            /*
            | النسبة الأصلية
            */

            $generalRawPercentage =
                ($generalDone / $records->count()) * 100;

            /*
            | الواجهة تعرض النسبة بالعشرات:
            | 93% -> 90%
            | 87% -> 80%
            */

            $generalPercentage =
                (int) (floor($generalRawPercentage / 10) * 10);

            /*
            | الربط اليومي
            */

            if (!$isEtqan) {

                $dailyDone = $records
                    ->where('daily_revision', true)
                    ->count();

                $dailyNotDone =
                    $records->count() - $dailyDone;

                $dailyRawPercentage =
                    ($dailyDone / $records->count()) * 100;

                $dailyPercentage =
                    (int) (floor($dailyRawPercentage / 10) * 10);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 13. Addition Days
        |--------------------------------------------------------------------------
        |
        | مهم:
        | نحسب الأيام الفعلية وليس عدد السجلات.
        |
        */

        $additionDates = $records
            ->pluck('addition_date')
            ->filter()
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->unique()
            ->sort()
            ->values();

        $additionDays = $additionDates->count();

        /*
        |--------------------------------------------------------------------------
        | 14. Interruption Days
        |--------------------------------------------------------------------------
        */

        $interruptionDays = 0;

        if ($additionDays >= 2) {

            $firstDate = Carbon::parse(
                $additionDates->first()
            );

            $lastDate = Carbon::parse(
                $additionDates->last()
            );

            $totalCalendarDays =
                $firstDate->diffInDays($lastDate) + 1;

            $interruptionDays =
                max(
                    0,
                    $totalCalendarDays - $additionDays
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 15. Addition Performance
        |--------------------------------------------------------------------------
        */

        $totalPages = 0;

        $averageAddition = 0;

        if (!$isEtqan) {

            $totalPages = round(
                (float) $records->sum('num_of_pages'),
                2
            );

            /*
            | متوسط الإضافة =
            | مجموع الصفحات ÷ أيام الإضافة
            */

            if ($additionDays > 0) {

                $averageAddition = round(
                    $totalPages / $additionDays,
                    2
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 16. Etqan Sheets
        |--------------------------------------------------------------------------
        */

        $totalEtqanSheets = 0;

        if ($isEtqan) {

            $totalEtqanSheets = round(
                (float) $records->sum('num_of_sheets'),
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 17. Attendance
        |--------------------------------------------------------------------------
        */

        $attendanceRecords = DB::table('attendances')
            ->where('user_id', $student->user_id)
            ->where('role', 'student')
            ->get();

        /*
        | عدد أيام التحضير
        */

        $totalAttendanceDays = $attendanceRecords
            ->pluck('insert_date')
            ->unique()
            ->count();

        /*
        | الحضور
        |
        | present + late = حاضر
        */

        $presentDays = $attendanceRecords
            ->whereIn(
                'attendance_state',
                ['present', 'late']
            )
            ->pluck('insert_date')
            ->unique()
            ->count();

        /*
        | الغياب
        */

        $absentDays = $attendanceRecords
            ->where(
                'attendance_state',
                'absent'
            )
            ->pluck('insert_date')
            ->unique()
            ->count();

        /*
        | نسبة الحضور
        */

        $attendancePercentage = 0;

        if ($totalAttendanceDays > 0) {

            $attendancePercentage = (int) round(
                ($presentDays / $totalAttendanceDays) * 100
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 18. Student Dates
        |--------------------------------------------------------------------------
        */

        $birthdate = $user->barthdate ?? null;

        $joinDate = $user->join_date ?? null;

        /*
        |--------------------------------------------------------------------------
        | 19. Format Join Date
        |--------------------------------------------------------------------------
        */

        $formattedJoinDate = $joinDate;

        if ($joinDate) {

            try {

                $formattedJoinDate = Carbon::parse($joinDate)
                    ->locale('ar')
                    ->translatedFormat('d F Y');

            } catch (\Throwable $e) {

                $formattedJoinDate = $joinDate;
            }
        }/*
|--------------------------------------------------------------------------
| Pledges Statistics
|--------------------------------------------------------------------------
*/

$pledges = DB::table('pledges_record')
    ->where('student_id', $student->id)
    ->get();

$systemPledges = $pledges->where('pledge_type', 'تعهد نظام')->count();

$absencePledges = $pledges->where('pledge_type', 'تعهد غياب')->count();

$additionPledges = $pledges->where('pledge_type', 'تعهد إضافة')->count();

$generalLinkPledges = $pledges->where('pledge_type', 'تعهد ربط عام')->count();

$totalPledges = $pledges->count();


$notes = DB::table('nots')
    ->join('teachers', 'nots.teacher_id', '=', 'teachers.id')
    ->join('users', 'teachers.user_id', '=', 'users.id')
    ->where('nots.student_id', $student->id)
    ->orderByDesc('nots.created_at')
    ->select(
        'nots.id',
        'nots.text_nots as note',
        'nots.created_at as date',
        'teachers.id as teacher_id',
        'users.name as teacher_name'
    )
    ->get();

 
        /*
        |--------------------------------------------------------------------------
        | 20. Build Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'تم جلب الملف الشخصي للسجل بنجاح',

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | معلومات الطالب
                |--------------------------------------------------------------------------
                */

                'student' => [

                    'id' => $student->id,

                    'user_id' => $student->user_id,

                    'name' => $user->name ?? null,

                    'halaqa' => [

                        'id' => $student->halaqa_id,

                        'name' =>
                            $halaqa->halaqa_type ?? null,
                    ],

                   'pledges' => [

                              'system' => $systemPledges,
                          
                              'absence' => $absencePledges,
                          
                              'addition' => $additionPledges,
                          
                              'general_link' => $generalLinkPledges,
                          
                              'total' => $totalPledges,
                          
                          ],

                  'notes' => $notes,
                    'region' =>
                        $user->region ?? null,

                    'birthdate' =>
                        $birthdate,

                    'join_date' =>
                        $joinDate,

                    'join_date_display' =>
                        $formattedJoinDate,

                    'stage' =>
                        $stage,
                ],

                /*
                |--------------------------------------------------------------------------
                | نوع السجل
                |--------------------------------------------------------------------------
                */

                'record_type' =>
                    $recordType,

                /*
                |--------------------------------------------------------------------------
                | معلومات الحفظ
                |--------------------------------------------------------------------------
                */

                'memorization' => [

                    /*
                    | السورة الحالية
                    */

                    'current_surah' =>
                        $currentSurah,

                    /*
                    | بداية الحفظ
                    */

                    'memorization_start' =>
                        $memorizationStart,

                    /*
                    | آخر تسميع
                    */

                    'last_recitation' =>
                        $lastRecitation,

                    /*
                    | حالة الحفظ
                    */

                    'memorization_state' =>
                        $memorizationState,
                ],

                /*
                |--------------------------------------------------------------------------
                | متابعة الربط
                |--------------------------------------------------------------------------
                */

                'revision' => [

                    /*
                    | الربط العام
                    */

                    'general' => [

                        'percentage' =>
                            $generalPercentage,

                        'done' =>
                            $generalDone,

                        'not_done' =>
                            $generalNotDone,

                        'total' =>
                            $generalDone + $generalNotDone,
                    ],

                    /*
                    | الربط اليومي
                    */

                    'daily' => [

                        'percentage' =>
                            $dailyPercentage,

                        'done' =>
                            $dailyDone,

                        'not_done' =>
                            $dailyNotDone,

                        'total' =>
                            $dailyDone + $dailyNotDone,
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | أداء الإضافة
                |--------------------------------------------------------------------------
                */

                'addition_performance' => [

                    'addition_days' =>
                        $additionDays,

                    'interruption_days' =>
                        $interruptionDays,

                    'average_pages' =>
                        $averageAddition,

                    'total_pages' =>
                        $totalPages,
                ],

                /*
                |--------------------------------------------------------------------------
                | الحضور
                |--------------------------------------------------------------------------
                */

                'attendance' => [

                    'percentage' =>
                        $attendancePercentage,

                    'total_days' =>
                        $totalAttendanceDays,

                    'present_days' =>
                        $presentDays,

                    'absent_days' =>
                        $absentDays,
                ],

                /*
                |--------------------------------------------------------------------------
                | الإتقان
                |--------------------------------------------------------------------------
                */

                'etqan' => [

                    'total_sheets' =>
                        $totalEtqanSheets,
                ],

                /*
                |--------------------------------------------------------------------------
                | جميع السجلات
                |--------------------------------------------------------------------------
                */

                'records' => $records,
            ],

        ], 200);
    }
}