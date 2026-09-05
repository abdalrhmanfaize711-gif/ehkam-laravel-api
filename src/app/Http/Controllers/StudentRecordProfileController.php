<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StudentRecordProfileRequest;

use App\Models\StudentModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentRecordProfileController extends Controller
{
    /**
     * Student Record Profile
     *
     * يرجع البيانات المطلوبة لواجهة:
     * الملف الشخصي للطالب
     */
    public function getStudentRecordProfile(StudentRecordProfileRequest $request): JsonResponse
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
        */

        $stage = trim((string) ($student->stage ?? ''));

        /*
        |--------------------------------------------------------------------------
        | إذا كانت المرحلة تحتوي على "إتقان"
        | فهذا طالب إتقان.
        |
        | مثل:
        | إتقان أول
        | إتقان ثاني
        | إتقان ثالث
        |--------------------------------------------------------------------------
        */

        $isEtqan = mb_strpos($stage, 'إتقان') !== false;

        $recordType = $isEtqan
            ? 'etqan'
            : 'addition';

        /*
        |--------------------------------------------------------------------------
        | 6. Table Name
        |--------------------------------------------------------------------------
        */

        $tableName = $isEtqan
            ? 'etqan_record'
            : 'addition_records';

       /*
|--------------------------------------------------------------------------
| 7. ALL RECORDS
|--------------------------------------------------------------------------
|
| نحتاج جميع السجلات لحساب:
| - أيام الحفظ
| - أيام الانقطاع
| - نسبة الربط العام
| - نسبة الربط اليومي
|
*/

$allRecords = DB::table($tableName)
    ->where('student_id', $studentId)
    ->orderBy('addition_date', 'asc')
    ->orderBy('id', 'asc')
    ->get();


/*
|--------------------------------------------------------------------------
| 8. SAVED RECORDS
|--------------------------------------------------------------------------
|
| جميع السجلات التي حالتها "حفظ".
|
*/

$savedRecords = $allRecords
    ->filter(function ($record) {

        return trim((string) $record->memorization_state) === 'حفظ';

    })
    ->values();


/*
|--------------------------------------------------------------------------
| 9. NOT SAVED RECORDS
|--------------------------------------------------------------------------
*/

$notSavedRecords = $allRecords
    ->filter(function ($record) {

        return trim((string) $record->memorization_state) === 'لم يحفظ';

    })
    ->values();


/*
|--------------------------------------------------------------------------
| 10. FIRST SAVED RECORD
|--------------------------------------------------------------------------
|
| أول Record فعلي حالته حفظ.
|
*/

$firstRecord = DB::table($tableName)
    ->where('student_id', $studentId)
    ->whereRaw("TRIM(memorization_state) = ?", ['حفظ'])
    ->orderBy('addition_date', 'asc')
    ->orderBy('id', 'asc')
    ->first();


/*
|--------------------------------------------------------------------------
| 11. LAST SAVED RECORD
|--------------------------------------------------------------------------
|
| مهم جدًا:
|
| لا نستخدم:
|
| $savedRecords->last()
|
| وإنما نعمل Query مباشر.
|
| آخر Record حالته "حفظ" هو الذي نريده.
|
| id DESC مهم جدًا لأنه يمثل آخر سجل تم إدخاله.
|
*/

$lastRecord = DB::table($tableName)
    ->where('student_id', $studentId)
    ->whereRaw("TRIM(memorization_state) = ?", ['حفظ'])
    ->orderByDesc('id')
    ->first();


/*
|--------------------------------------------------------------------------
| 12. MEMORIZATION START
|--------------------------------------------------------------------------
*/

$memorizationStart = null;

if ($firstRecord) {

    $memorizationStart = [

        'record_id' =>
            $firstRecord->id,

        'surah' =>
            $firstRecord->from_surah,

        'ayah' =>
            $firstRecord->from_ayah,

        'date' =>
            $firstRecord->addition_date,

        'display' =>
            $firstRecord->from_surah .
            ' - الآية ' .
            $firstRecord->from_ayah,
    ];
}


/*
|--------------------------------------------------------------------------
| 13. CURRENT SURAH
|--------------------------------------------------------------------------
|
| هذا هو المكان المهم.
|
| current_surah تأتي من:
|
| آخر Record في قاعدة البيانات
| للطالب
| حالته = حفظ
|
| وليس آخر Record بشكل عام.
|
*/

$currentSurah = null;

if ($lastRecord) {

    $currentSurah = [

        'record_id' =>
            $lastRecord->id,

        'surah' =>
            $lastRecord->to_surah,

        'ayah' =>
            $lastRecord->to_ayah,

        'date' =>
            $lastRecord->addition_date,

        'display' =>
            $lastRecord->to_surah .
            ' - الآية ' .
            $lastRecord->to_ayah,
    ];
}


/*
|--------------------------------------------------------------------------
| 14. LAST RECITATION
|--------------------------------------------------------------------------
|
| نفس الـ $lastRecord بالضبط.
|
| لذلك:
|
| current_surah
| last_recitation
| memorization_state
|
| كلها مأخوذة من نفس آخر Record حالته "حفظ".
|
*/

$lastRecitation = null;

if ($lastRecord) {

    $lastRecitation = [

        'record_id' =>
            $lastRecord->id,

        'from' => [

            'surah' =>
                $lastRecord->from_surah,

            'ayah' =>
                $lastRecord->from_ayah,
        ],

        'to' => [

            'surah' =>
                $lastRecord->to_surah,

            'ayah' =>
                $lastRecord->to_ayah,
        ],

        'date' =>
            $lastRecord->addition_date,

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
| 15. MEMORIZATION STATE
|--------------------------------------------------------------------------
|
| لا نأخذ الحالة من آخر Record بشكل عام.
|
| نأخذها من آخر Record حالته أصلًا "حفظ".
|
*/

$memorizationState =
    $lastRecord
        ? trim((string) $lastRecord->memorization_state)
        : null;

        /*
        |--------------------------------------------------------------------------
        | 16. UNIQUE FOLLOW-UP DATES
        |--------------------------------------------------------------------------
        |
        | كل تاريخ يحسب مرة واحدة فقط.
        |--------------------------------------------------------------------------
        */

        $allFollowUpDates = $allRecords
            ->pluck('addition_date')
            ->filter()
            ->map(function ($date) {

                try {

                    return Carbon::parse($date)
                        ->format('Y-m-d');

                } catch (\Throwable $e) {

                    return null;
                }
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
/*
|--------------------------------------------------------------------------
| GENERAL REVISION
|--------------------------------------------------------------------------
*/

// جلب جميع سجلات الطالب موجود أصلًا في $allRecords

$generalDone = 0;
$generalNotDone = 0;
$generalTotal = 0;
$generalPercentage = 0;


/*
|--------------------------------------------------------------------------
| حساب true / false
|--------------------------------------------------------------------------
*/

foreach ($allRecords as $record) {

    /*
    | إذا كانت القيمة true أو 1
    */

    if (
        $record->general_revision === true ||
        $record->general_revision === 1 ||
        $record->general_revision === '1' ||
        $record->general_revision === 'true'
    ) {

        $generalDone++;

    }

    /*
    | إذا كانت القيمة false أو 0
    */

    elseif (
        $record->general_revision === false ||
        $record->general_revision === 0 ||
        $record->general_revision === '0' ||
        $record->general_revision === 'false'
    ) {

        $generalNotDone++;
    }
}


/*
|--------------------------------------------------------------------------
| إجمالي سجلات الربط العام
|--------------------------------------------------------------------------
*/

$generalTotal =
    $generalDone +
    $generalNotDone;


/*
|--------------------------------------------------------------------------
| نسبة الربط العام
|--------------------------------------------------------------------------
*/

if ($generalTotal > 0) {

    $generalPercentage = (int) round(
        ($generalDone / $generalTotal) * 100
    );

}
        /*
        |--------------------------------------------------------------------------
        | 18. DAILY REVISION
        |--------------------------------------------------------------------------
        |
        | الربط اليومي خاص بالإضافة.
        |--------------------------------------------------------------------------
        */

        $dailyDone = 0;

        $dailyNotDone = 0;

        $dailyTotal = 0;

        $dailyPercentage = 0;

        if (!$isEtqan) {

            $dailyTotal =
                $allFollowUpDates->count();

            /*
            |--------------------------------------------------------------------------
            | Daily Done Dates
            |--------------------------------------------------------------------------
            */

            $dailyDoneDates = $allRecords
                ->filter(function ($record) {

                    return trim(
                        (string) $record->memorization_state
                    ) === 'حفظ'
                    &&
                    (int) $record->daily_revision === 1;

                })
                ->pluck('addition_date')
                ->filter()
                ->map(function ($date) {

                    try {

                        return Carbon::parse($date)
                            ->format('Y-m-d');

                    } catch (\Throwable $e) {

                        return null;
                    }
                })
                ->filter()
                ->unique()
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Daily Done
            |--------------------------------------------------------------------------
            */

            $dailyDone =
                $dailyDoneDates->count();

            /*
            |--------------------------------------------------------------------------
            | Daily Not Done
            |--------------------------------------------------------------------------
            */

            $dailyNotDone = max(
                $dailyTotal - $dailyDone,
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Daily Percentage
            |--------------------------------------------------------------------------
            */

            if ($dailyTotal > 0) {

                $dailyPercentage = (int) round(
                    ($dailyDone / $dailyTotal) * 100
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 19. SAVED ADDITION DAYS
        |--------------------------------------------------------------------------
        |
        | أيام الحفظ فقط.
        |--------------------------------------------------------------------------
        */

        $additionDates = $savedRecords
            ->pluck('addition_date')
            ->filter()
            ->map(function ($date) {

                try {

                    return Carbon::parse($date)
                        ->format('Y-m-d');

                } catch (\Throwable $e) {

                    return null;
                }
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $additionDays =
            $additionDates->count();

        /*
        |--------------------------------------------------------------------------
        | 20. INTERRUPTION DAYS
        |--------------------------------------------------------------------------
        |
        | أيام "لم يحفظ" فقط.
        |
        | كل يوم يحسب مرة واحدة.
        |--------------------------------------------------------------------------
        */

        $interruptionDates = $notSavedRecords
            ->pluck('addition_date')
            ->filter()
            ->map(function ($date) {

                try {

                    return Carbon::parse($date)
                        ->format('Y-m-d');

                } catch (\Throwable $e) {

                    return null;
                }
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $interruptionDays =
            $interruptionDates->count();

        /*
        |--------------------------------------------------------------------------
        | 21. ADDITION PERFORMANCE
        |--------------------------------------------------------------------------
        */

        $totalPages = 0;

        $averageAddition = 0;

        if (!$isEtqan) {

            /*
            |--------------------------------------------------------------------------
            | Total Pages
            |--------------------------------------------------------------------------
            |
            | فقط السجلات التي حالتها "حفظ".
            |--------------------------------------------------------------------------
            */

            $totalPages = round(
                (float) $savedRecords->sum('num_of_pages'),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Average Pages
            |--------------------------------------------------------------------------
            |
            | إجمالي الصفحات المحفوظة
            | ÷ أيام الحفظ الفعلية.
            |--------------------------------------------------------------------------
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
        | 22. ETQAN SHEETS
        |--------------------------------------------------------------------------
        */

        $totalEtqanSheets = 0;

        if ($isEtqan) {

            $totalEtqanSheets = round(
                (float) $savedRecords->sum('num_of_sheets'),
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 23. ATTENDANCE
        |--------------------------------------------------------------------------
        */

        $attendanceRecords = DB::table('attendances')
            ->where('user_id', $student->user_id)
            ->where('role', 'student')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Total Attendance Days
        |--------------------------------------------------------------------------
        */

        $totalAttendanceDays = $attendanceRecords
            ->pluck('insert_date')
            ->filter()
            ->map(function ($date) {

                try {

                    return Carbon::parse($date)
                        ->format('Y-m-d');

                } catch (\Throwable $e) {

                    return null;
                }
            })
            ->filter()
            ->unique()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Present Days
        |--------------------------------------------------------------------------
        */

        $presentDays = $attendanceRecords
            ->filter(function ($record) {

                return in_array(
                    trim((string) $record->attendance_state),
                    [
                        'present',
                        'late',
                        'حضور',
                        'حاضر',
                        'تأخر',
                        'متأخر',
                    ]
                );
            })
            ->pluck('insert_date')
            ->filter()
            ->map(function ($date) {

                try {

                    return Carbon::parse($date)
                        ->format('Y-m-d');

                } catch (\Throwable $e) {

                    return null;
                }
            })
            ->filter()
            ->unique()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Absent Days
        |--------------------------------------------------------------------------
        */

        $absentDays = $attendanceRecords
            ->filter(function ($record) {

                return in_array(
                    trim((string) $record->attendance_state),
                    [
                        'absent',
                        'غياب',
                        'غائب',
                    ]
                );
            })
            ->pluck('insert_date')
            ->filter()
            ->map(function ($date) {

                try {

                    return Carbon::parse($date)
                        ->format('Y-m-d');

                } catch (\Throwable $e) {

                    return null;
                }
            })
            ->filter()
            ->unique()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Attendance Percentage
        |--------------------------------------------------------------------------
        */

        $attendancePercentage = 0;

        if ($totalAttendanceDays > 0) {

            $attendancePercentage = (int) round(
                ($presentDays / $totalAttendanceDays) * 100
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 24. Student Dates
        |--------------------------------------------------------------------------
        */

        $birthdate =
            $user->barthdate ?? null;

        $joinDate =
            $user->join_date ?? null;

        /*
        |--------------------------------------------------------------------------
        | 25. Format Join Date
        |--------------------------------------------------------------------------
        */

        $formattedJoinDate = $joinDate;

        if ($joinDate) {

            try {

                $formattedJoinDate = Carbon::parse(
                    $joinDate
                )
                    ->locale('ar')
                    ->translatedFormat('d F Y');

            } catch (\Throwable $e) {

                $formattedJoinDate =
                    $joinDate;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 26. NOTES
        |--------------------------------------------------------------------------
        */

        $notes = DB::table('nots')
            ->join(
                'teachers',
                'nots.teacher_id',
                '=',
                'teachers.id'
            )
            ->join(
                'users',
                'teachers.user_id',
                '=',
                'users.id'
            )
            ->where(
                'nots.student_id',
                $student->id
            )
            ->orderByDesc(
                'nots.created_at'
            )
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
        | 27. BUILD RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'تم جلب الملف الشخصي للسجل بنجاح',

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | Student
                |--------------------------------------------------------------------------
                */

                'student' => [

                    'id' =>
                        $student->id,

                    'user_id' =>
                        $student->user_id,

                    'name' =>
                        $user->name ?? null,

                    'halaqa' => [

                        'id' =>
                            $student->halaqa_id,

                        'name' =>
                            $halaqa->halaqa_type ?? null,
                    ],

                    'notes' =>
                        $notes,

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
                | Record Type
                |--------------------------------------------------------------------------
                */

                'record_type' =>
                    $recordType,

                /*
                |--------------------------------------------------------------------------
                | Memorization
                |--------------------------------------------------------------------------
                */

                'memorization' => [

                    /*
                    |--------------------------------------------------------------------------
                    | Current Surah
                    |--------------------------------------------------------------------------
                    |
                    | آخر سجل حالته "حفظ" فقط.
                    |--------------------------------------------------------------------------
                    */

                    'current_surah' =>
                        $currentSurah,

                    /*
                    |--------------------------------------------------------------------------
                    | Memorization Start
                    |--------------------------------------------------------------------------
                    |
                    | أول سجل حالته "حفظ".
                    |--------------------------------------------------------------------------
                    */

                    'memorization_start' =>
                        $memorizationStart,

                    /*
                    |--------------------------------------------------------------------------
                    | Last Recitation
                    |--------------------------------------------------------------------------
                    |
                    | آخر سجل حالته "حفظ" فقط.
                    |--------------------------------------------------------------------------
                    */

                    'last_recitation' =>
                        $lastRecitation,

                    /*
                    |--------------------------------------------------------------------------
                    | Memorization State
                    |--------------------------------------------------------------------------
                    */

                    'memorization_state' =>
                        $memorizationState,
                ],

                /*
                |--------------------------------------------------------------------------
                | Revision
                |--------------------------------------------------------------------------
                */

                'revision' => [

                    /*
                    |--------------------------------------------------------------------------
                    | General Revision
                    |--------------------------------------------------------------------------
                    */

                    'general' => [

                        'percentage' =>
                            $generalPercentage,

                        'done' =>
                            $generalDone,

                        'not_done' =>
                            $generalNotDone,

                        'total' =>
                            $generalTotal,
                    ],

                    /*
                    |--------------------------------------------------------------------------
                    | Daily Revision
                    |--------------------------------------------------------------------------
                    */

                    'daily' => [

                        'percentage' =>
                            $dailyPercentage,

                        'done' =>
                            $dailyDone,

                        'not_done' =>
                            $dailyNotDone,

                        'total' =>
                            $dailyTotal,
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | Addition Performance
                |--------------------------------------------------------------------------
                */

                'addition_performance' => [

                    /*
                    | أيام الحفظ الفعلية
                    */

                    'addition_days' =>
                        $additionDays,

                    /*
                    | أيام الانقطاع
                    */

                    'interruption_days' =>
                        $interruptionDays,

                    /*
                    | متوسط الصفحات
                    */

                    'average_pages' =>
                        $averageAddition,

                    /*
                    | إجمالي الصفحات
                    |
                    | من سجلات "حفظ" فقط.
                    */

                    'total_pages' =>
                        $totalPages,
                ],

                /*
                |--------------------------------------------------------------------------
                | Attendance
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
                | Etqan
                |--------------------------------------------------------------------------
                */

                'etqan' => [

                    'total_sheets' =>
                        $totalEtqanSheets,
                ],

                /*
                |--------------------------------------------------------------------------
                | Records
                |--------------------------------------------------------------------------
                |
                | نرجع فقط سجلات "حفظ".
                |
                | آخر سجل في هذه القائمة هو آخر تسميع محفوظ
                |--------------------------------------------------------------------------
                */

                'records' =>
                    $savedRecords,

            ],

        ], 200);
    }
}