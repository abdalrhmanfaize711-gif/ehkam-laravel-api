<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\GetAllAttendancesRequest;
use App\Http\Requests\Api\GetSpecialAttendancesRequest;
use Illuminate\Http\Request;
use App\Models\AttendancesModel;
use App\Models\NotificationsModel;
use App\Models\StudentModel;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\getAbsentStudentByDateRequest;
use App\Http\Requests\Api\AddLateAttendanceRequest;
use App\Http\Requests\Api\AddAttendancesRequest;
use App\Http\Requests\Api\GetTodayAttendancePercentageRequest;
use App\Http\Requests\Api\GetLastFourAttendanceDatesRequest;


class AttendancesController extends Controller
{

    // ____________________________________________________________
    // نسبة الحضور اليوم
    // ____________________________________________________________

    public function getTodayAttendancePercentage()
    {
        // Total users (students + teachers)
        $totalUsers = DB::table('users')->count();

        // Present today (present + late)
        $presentUsers = AttendancesModel::whereDate(
                'insert_date',
                today()
            )
            ->whereIn(
                'attendance_state',
                ['present', 'late']
            )
            ->count();

        // Attendance percentage
        $attendancePercentage = $totalUsers > 0
            ? round(($presentUsers / $totalUsers) * 100)
            : 0;

        return response()->json([
            'success' => true,
            'message' => 'تم استرجاع نسبة الحضور اليوم بنجاح.',
            'data' => [
                'attendance_percentage' => $attendancePercentage,
                'total_users' => $totalUsers,
                'present_users' => $presentUsers,
                'absent_users' => $totalUsers - $presentUsers,
            ]
        ], 200);
    }


    // ____________________________________________________________
    // آخر 4 تواريخ تم فيها تسجيل التحضير للطلاب
    // ____________________________________________________________

    public function getLastFourAttendanceDates()
    {
        $dates = DB::table('attendances')
            ->where(
                'role',
                'student'
            )
            ->select(
                DB::raw('DATE(insert_date) as date')
            )
            ->distinct()
            ->orderByDesc('date')
            ->limit(4)
            ->pluck('date');

        return response()->json([
            'success' => true,
            'dates' => $dates
        ], 200);
    }


    // ____________________________________________________________
    // جلب الطلاب الغائبين حسب التاريخ
    // ____________________________________________________________

    public function getAbsentStudentByDate(getAbsentStudentByDateRequest $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $students = DB::table('attendances')
            ->join(
                'users',
                'users.id',
                '=',
                'attendances.user_id'
            )
            ->join(
                'students',
                'students.user_id',
                '=',
                'users.id'
            )
            ->where(
                'attendances.role',
                'student'
            )
            ->where(
                'attendances.attendance_state',
                'absent'
            )
            ->whereDate(
                'attendances.insert_date',
                $request->insert_date
            )
            ->select(
                'students.id as student_id',
                'users.id as user_id',
                'users.name',
                'students.stage',
                'students.halaqa_id'
            )
            ->orderBy(
                'users.name'
            )
            ->get();

        return response()->json([
            'success' => true,
            'date' => $request->insert_date,
            'count' => $students->count(),
            'students' => $students
        ], 200);
    }


    // ____________________________________________________________
    // إضافة تأخر لطالب
    // ____________________________________________________________
    //
    // Flutter يرسل:
    //
    // {
    //     "user_id": 14,
    //     "attendance_state": "late"
    // }
    //
    // المنطق:
    //
    // 1. نأخذ آخر تاريخ حضور للطالب.
    //
    // 2. نبحث عن سجل الطالب في ذلك التاريخ.
    //
    // 3. إذا كان absent:
    //       UPDATE نفس الـ ROW
    //       absent -> late
    //
    // 4. إذا لم يوجد سجل:
    //       CREATE late جديد
    //
    // 5. إذا كان late أصلًا:
    //       لا ننشئ Row جديد.
    //
    // ____________________________________________________________

    public function addLateAttendance(AddLateAttendanceRequest $request)
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | التحقق من البيانات
            |--------------------------------------------------------------------------
            */

            if (
                !$request->has('user_id') ||
                !$request->has('attendance_state')
            ) {
                DB::rollBack();

                return response()->json([
                    'message' => 'user_id و attendance_state مطلوبة'
                ], 400);
            }


            /*
            |--------------------------------------------------------------------------
            | استقبال البيانات
            |--------------------------------------------------------------------------
            */

            $userId = $request->user_id;

            $attendanceState = $request->attendance_state;


            /*
            |--------------------------------------------------------------------------
            | التأكد أن الحالة late
            |--------------------------------------------------------------------------
            */

            if ($attendanceState !== 'late') {

                DB::rollBack();

                return response()->json([
                    'message' => 'attendance_state يجب أن تكون late'
                ], 400);
            }


            /*
            |--------------------------------------------------------------------------
            | التأكد من وجود الطالب
            |--------------------------------------------------------------------------
            */

            $student = StudentModel::where(
                'user_id',
                $userId
            )->first();

            if (!$student) {

                DB::rollBack();

                return response()->json([
                    'message' => 'لا يوجد طالب مرتبط بهذا المستخدم'
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | الحصول على آخر تاريخ حضور للطالب
            |--------------------------------------------------------------------------
            |
            | مثال:
            |
            | id | user_id | role    | state  | insert_date
            | ------------------------------------------------
            | 25 | 14      | student | absent | 2026-08-11
            | 28 | 14      | student | late   | 2026-08-09
            |
            | النتيجة:
            |
            | lastDate = 2026-08-11
            |
            */

            $lastAttendance = AttendancesModel::where(
                    'user_id',
                    $userId
                )
                ->where(
                    'role',
                    'student'
                )
                ->orderByDesc(
                    'insert_date'
                )
                ->orderByDesc(
                    'id'
                )
                ->first();


            /*
            |--------------------------------------------------------------------------
            | تحديد آخر تاريخ
            |--------------------------------------------------------------------------
            */

            if ($lastAttendance) {

                $lastDate = $lastAttendance->insert_date;

            } else {

                /*
                |--------------------------------------------------------------------------
                | لا يوجد أي سجل سابق
                |--------------------------------------------------------------------------
                */

                $lastDate = now()->format('Y-m-d');
            }


            /*
            |--------------------------------------------------------------------------
            | البحث عن سجل الطالب في آخر تاريخ
            |--------------------------------------------------------------------------
            */

            $existingAttendance = AttendancesModel::where(
                    'user_id',
                    $userId
                )
                ->where(
                    'role',
                    'student'
                )
                ->whereDate(
                    'insert_date',
                    $lastDate
                )
                ->orderByDesc(
                    'id'
                )
                ->first();


            /*
            |--------------------------------------------------------------------------
            | يوجد سجل في آخر تاريخ
            |--------------------------------------------------------------------------
            */

            if ($existingAttendance) {

                /*
                |--------------------------------------------------------------------------
                | الحالة absent
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                |
                | لا نعمل CREATE.
                |
                | نعدل نفس الـ ROW.
                |
                | absent -> late
                |
                */

                if (
                    $existingAttendance->attendance_state === 'absent'
                ) {

                    $existingAttendance->update([
                        'attendance_state' => 'late',
                    ]);

                    $existingAttendance->refresh();


                    /*
                    |--------------------------------------------------------------------------
                    | حذف إشعار الغياب لنفس التاريخ
                    |--------------------------------------------------------------------------
                    */

                    NotificationsModel::where(
                        'student_id',
                        $student->id
                    )
                    ->where(
                        'title',
                        'إشعار غياب'
                    )
                    ->whereDate(
                        'insert_date',
                        $lastDate
                    )
                    ->delete();


                    /*
                    |--------------------------------------------------------------------------
                    | حساب عدد مرات التأخر
                    |--------------------------------------------------------------------------
                    */

                    $lateCount = AttendancesModel::where(
                            'user_id',
                            $userId
                        )
                        ->where(
                            'role',
                            'student'
                        )
                        ->where(
                            'attendance_state',
                            'late'
                        )
                        ->count();


                    $notification = null;


                    /*
                    |--------------------------------------------------------------------------
                    | إذا وصل إلى 3 مرات تأخر
                    |--------------------------------------------------------------------------
                    */

                    if ($lateCount >= 3) {

                        $notification = NotificationsModel::create([

                            'student_id' =>
                                $student->id,

                            'halaqa_id' =>
                                $student->halaqa_id,

                            'title' =>
                                'إشعار غياب بسبب التأخر',

                            'notification_time' =>
                                now()->format('H:i:s'),

                            'insert_date' =>
                                $lastDate,

                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | حذف جميع سجلات التأخر
                        |--------------------------------------------------------------------------
                        */

                        AttendancesModel::where(
                            'user_id',
                            $userId
                        )
                        ->where(
                            'role',
                            'student'
                        )
                        ->where(
                            'attendance_state',
                            'late'
                        )
                        ->delete();
                    }


                    DB::commit();


                    return response()->json([

                        'message' =>
                            'تم تحويل سجل الغياب إلى تأخر بنجاح',

                        'action' =>
                            'updated',

                        'last_date' =>
                            $lastDate,

                        'late_count' =>
                            $lateCount,

                        'attendance' =>
                            $existingAttendance,

                        'notification' =>
                            $notification,

                    ], 200);
                }


                /*
                |--------------------------------------------------------------------------
                | إذا كان late أصلًا
                |--------------------------------------------------------------------------
                */

                if (
                    $existingAttendance->attendance_state === 'late'
                ) {

                    DB::rollBack();

                    return response()->json([

                        'message' =>
                            'الطالب مسجل كمتأخر مسبقًا في آخر تاريخ',

                        'action' =>
                            'already_late',

                        'last_date' =>
                            $lastDate,

                        'attendance' =>
                            $existingAttendance,

                    ], 409);
                }


                /*
                |--------------------------------------------------------------------------
                | حالة أخرى
                |--------------------------------------------------------------------------
                */

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'يوجد سجل حضور للطالب في آخر تاريخ بحالة أخرى',

                    'action' =>
                        'existing',

                    'last_date' =>
                        $lastDate,

                    'attendance' =>
                        $existingAttendance,

                ], 409);
            }


            /*
            |--------------------------------------------------------------------------
            | لا يوجد سجل في آخر تاريخ
            |--------------------------------------------------------------------------
            |
            | هنا فقط نقوم بإنشاء late جديد.
            |
            */

            $newAttendance = AttendancesModel::create([

                'user_id' =>
                    $userId,

                'role' =>
                    'student',

                'attendance_state' =>
                    'late',

                'insert_date' =>
                    $lastDate,

            ]);


            /*
            |--------------------------------------------------------------------------
            | حساب عدد مرات التأخر
            |--------------------------------------------------------------------------
            */

            $lateCount = AttendancesModel::where(
                    'user_id',
                    $userId
                )
                ->where(
                    'role',
                    'student'
                )
                ->where(
                    'attendance_state',
                    'late'
                )
                ->count();


            $notification = null;


            /*
            |--------------------------------------------------------------------------
            | إذا وصل إلى 3 مرات تأخر
            |--------------------------------------------------------------------------
            */

            if ($lateCount >= 3) {

                $notification = NotificationsModel::create([

                    'student_id' =>
                        $student->id,

                    'halaqa_id' =>
                        $student->halaqa_id,

                    'title' =>
                        'إشعار غياب بسبب التأخر',

                    'notification_time' =>
                        now()->format('H:i:s'),

                    'insert_date' =>
                        $lastDate,

                ]);


                /*
                |--------------------------------------------------------------------------
                | حذف جميع سجلات التأخر
                |--------------------------------------------------------------------------
                */

                AttendancesModel::where(
                    'user_id',
                    $userId
                )
                ->where(
                    'role',
                    'student'
                )
                ->where(
                    'attendance_state',
                    'late'
                )
                ->delete();
            }


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'message' =>
                    'تم إضافة سجل التأخر بنجاح',

                'action' =>
                    'created',

                'last_date' =>
                    $lastDate,

                'late_count' =>
                    $lateCount,

                'attendance' =>
                    $newAttendance,

                'notification' =>
                    $notification,

            ], 201);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'message' =>
                    'حدث خطأ أثناء إضافة سجل التأخر',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }


    // ____________________________________________________________
    // إضافة التحضير
    // ____________________________________________________________

    public function add_attendances(AddAttendancesRequest $request)
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | التحقق من وجود بيانات التحضير
            |--------------------------------------------------------------------------
            */

            if (
                !$request->has('attendances') ||
                empty($request->attendances)
            ) {

                DB::rollBack();

                return response()->json([
                    'message' => 'لا توجد معلومات للحضور'
                ], 400);
            }


            /*
            |--------------------------------------------------------------------------
            | التحقق من البيانات
            |--------------------------------------------------------------------------
            */

            foreach ($request->attendances as $attendance) {

                if (
                    !isset($attendance['user_id']) ||
                    !isset($attendance['role']) ||
                    !isset($attendance['attendance_state']) ||
                    !isset($attendance['insert_date'])
                ) {
                    throw new \Exception(
                        'user_id و role و attendance_state و insert_date مطلوبة'
                    );
                }


                if (
                    !in_array(
                        $attendance['role'],
                        ['student', 'teacher']
                    )
                ) {
                    throw new \Exception(
                        'role يجب أن يكون student أو teacher'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | فحص التكرار في قاعدة البيانات
            |--------------------------------------------------------------------------
            */

            foreach ($request->attendances as $attendance) {

                $existingAttendance = AttendancesModel::where(
                        'user_id',
                        $attendance['user_id']
                    )
                    ->where(
                        'role',
                        $attendance['role']
                    )
                    ->whereDate(
                        'insert_date',
                        $attendance['insert_date']
                    )
                    ->first();


                if ($existingAttendance) {

                    /*
                    |--------------------------------------------------------------------------
                    | الاستثناء:
                    |
                    | absent -> late
                    |
                    | سيتم تعديل نفس الـ ROW لاحقًا.
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $attendance['role'] === 'student' &&
                        $attendance['attendance_state'] === 'late' &&
                        $existingAttendance->attendance_state === 'absent'
                    ) {
                        continue;
                    }


                    $userName = DB::table('users')
                        ->where(
                            'id',
                            $attendance['user_id']
                        )
                        ->value('name');


                    $roleName =
                        $attendance['role'] === 'student'
                            ? 'الطالب'
                            : 'المعلم';


                    throw new \Exception(
                        $userName
                            ? "{$roleName} {$userName} تم تحضيره مسبقًا في تاريخ {$attendance['insert_date']}"
                            : "هذا {$roleName} تم تحضيره مسبقًا في تاريخ {$attendance['insert_date']}"
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | فحص التكرار داخل نفس الطلب
            |--------------------------------------------------------------------------
            */

            $checkedUsers = [];


            foreach ($request->attendances as $attendance) {

                $date = date(
                    'Y-m-d',
                    strtotime($attendance['insert_date'])
                );


                $key =
                    $attendance['user_id']
                    . '_'
                    . $attendance['role']
                    . '_'
                    . $date;


                if (in_array($key, $checkedUsers)) {

                    $roleName =
                        $attendance['role'] === 'student'
                            ? 'الطالب'
                            : 'المعلم';


                    throw new \Exception(
                        "تم إرسال {$roleName} أكثر من مرة في نفس التاريخ {$date}"
                    );
                }


                $checkedUsers[] = $key;
            }


            /*
            |--------------------------------------------------------------------------
            | Arrays
            |--------------------------------------------------------------------------
            */

            $attendances = [];

            $notifications = [];


            /*
            |--------------------------------------------------------------------------
            | حفظ التحضير
            |--------------------------------------------------------------------------
            */

            foreach ($request->attendances as $attendance) {

                /*
                |--------------------------------------------------------------------------
                | معالجة late للطالب
                |--------------------------------------------------------------------------
                |
                | إذا كان هناك absent في نفس التاريخ:
                |
                | UPDATE فقط.
                |
                | لا CREATE.
                |
                |--------------------------------------------------------------------------
                */

                if (
                    $attendance['role'] === 'student' &&
                    $attendance['attendance_state'] === 'late'
                ) {

                    $existingAttendance = AttendancesModel::where(
                            'user_id',
                            $attendance['user_id']
                        )
                        ->where(
                            'role',
                            'student'
                        )
                        ->whereDate(
                            'insert_date',
                            $attendance['insert_date']
                        )
                        ->orderByDesc('id')
                        ->first();


                    /*
                    |--------------------------------------------------------------------------
                    | absent -> late
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $existingAttendance &&
                        $existingAttendance->attendance_state === 'absent'
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE نفس الـ ROW
                        |--------------------------------------------------------------------------
                        */

                        $existingAttendance->update([
                            'attendance_state' => 'late',
                        ]);


                        $existingAttendance->refresh();


                        /*
                        |--------------------------------------------------------------------------
                        | إضافة السجل المعدل للنتيجة
                        |--------------------------------------------------------------------------
                        */

                        $attendances[] = $existingAttendance;


                        /*
                        |--------------------------------------------------------------------------
                        | الحصول على الطالب
                        |--------------------------------------------------------------------------
                        */

                        $student = StudentModel::where(
                            'user_id',
                            $attendance['user_id']
                        )->first();


                        if (!$student) {
                            throw new \Exception(
                                'لا يوجد طالب مرتبط بهذا المستخدم'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | حذف إشعار الغياب
                        |--------------------------------------------------------------------------
                        */

                        NotificationsModel::where(
                            'student_id',
                            $student->id
                        )
                        ->where(
                            'title',
                            'إشعار غياب'
                        )
                        ->whereDate(
                            'insert_date',
                            $attendance['insert_date']
                        )
                        ->delete();


                        /*
                        |--------------------------------------------------------------------------
                        | حساب مرات التأخر
                        |--------------------------------------------------------------------------
                        */

                        $lateCount = AttendancesModel::where(
                                'user_id',
                                $attendance['user_id']
                            )
                            ->where(
                                'role',
                                'student'
                            )
                            ->where(
                                'attendance_state',
                                'late'
                            )
                            ->count();


                        /*
                        |--------------------------------------------------------------------------
                        | إذا وصل إلى 3 مرات
                        |--------------------------------------------------------------------------
                        */

                        if ($lateCount >= 3) {

                            $notification =
                                NotificationsModel::create([

                                    'student_id' =>
                                        $student->id,

                                    'halaqa_id' =>
                                        $student->halaqa_id,

                                    'title' =>
                                        'إشعار غياب بسبب التأخر',

                                    'notification_time' =>
                                        now()->format('H:i:s'),

                                    'insert_date' =>
                                        $attendance['insert_date'],

                                ]);


                            $notifications[] = $notification;


                            /*
                            |--------------------------------------------------------------------------
                            | حذف سجلات التأخر
                            |--------------------------------------------------------------------------
                            */

                            AttendancesModel::where(
                                'user_id',
                                $attendance['user_id']
                            )
                            ->where(
                                'role',
                                'student'
                            )
                            ->where(
                                'attendance_state',
                                'late'
                            )
                            ->delete();
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | مهم جدًا جدًا
                        |--------------------------------------------------------------------------
                        |
                        | هنا نخرج من الدورة.
                        |
                        | حتى لا يصل التنفيذ إلى:
                        |
                        | AttendancesModel::create()
                        |
                        | وبالتالي لن يتم إنشاء late جديد.
                        |
                        |--------------------------------------------------------------------------
                        */

                        continue;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | إنشاء سجل التحضير
                |--------------------------------------------------------------------------
                |
                | يصل هنا فقط إذا لم يكن لدينا:
                |
                | absent -> late
                |
                |--------------------------------------------------------------------------
                */

                $attendanceRecord = AttendancesModel::create([

                    'user_id' =>
                        $attendance['user_id'],

                    'role' =>
                        $attendance['role'],

                    'attendance_state' =>
                        $attendance['attendance_state'],

                    'insert_date' =>
                        $attendance['insert_date'],

                ]);


                $attendances[] = $attendanceRecord;


                /*
                |--------------------------------------------------------------------------
                | معالجة late الجديد
                |--------------------------------------------------------------------------
                */

                if (
                    $attendance['role'] === 'student' &&
                    $attendance['attendance_state'] === 'late'
                ) {

                    $student = StudentModel::where(
                        'user_id',
                        $attendance['user_id']
                    )->first();


                    if (!$student) {
                        throw new \Exception(
                            'لا يوجد طالب مرتبط بهذا المستخدم'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | حساب عدد مرات التأخر
                    |--------------------------------------------------------------------------
                    */

                    $lateCount = AttendancesModel::where(
                            'user_id',
                            $attendance['user_id']
                        )
                        ->where(
                            'role',
                            'student'
                        )
                        ->where(
                            'attendance_state',
                            'late'
                        )
                        ->count();


                    /*
                    |--------------------------------------------------------------------------
                    | إذا وصل إلى 3 مرات
                    |--------------------------------------------------------------------------
                    */

                    if ($lateCount >= 3) {

                        $notification =
                            NotificationsModel::create([

                                'student_id' =>
                                    $student->id,

                                'halaqa_id' =>
                                    $student->halaqa_id,

                                'title' =>
                                    'إشعار غياب بسبب التأخر',

                                'notification_time' =>
                                    now()->format('H:i:s'),

                                'insert_date' =>
                                    $attendance['insert_date'],

                            ]);


                        $notifications[] = $notification;


                        /*
                        |--------------------------------------------------------------------------
                        | حذف جميع سجلات التأخر
                        |--------------------------------------------------------------------------
                        */

                        AttendancesModel::where(
                            'user_id',
                            $attendance['user_id']
                        )
                        ->where(
                            'role',
                            'student'
                        )
                        ->where(
                            'attendance_state',
                            'late'
                        )
                        ->delete();
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | معالجة absent
                |--------------------------------------------------------------------------
                */

                if (
                    $attendance['role'] === 'student' &&
                    $attendance['attendance_state'] === 'absent'
                ) {

                    $student = StudentModel::where(
                        'user_id',
                        $attendance['user_id']
                    )->first();


                    if (!$student) {
                        throw new \Exception(
                            'لا يوجد طالب مرتبط بهذا المستخدم'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | منع تكرار إشعار الغياب
                    |--------------------------------------------------------------------------
                    */

                    $notificationExists =
                        NotificationsModel::where(
                            'student_id',
                            $student->id
                        )
                        ->where(
                            'title',
                            'إشعار غياب'
                        )
                        ->whereDate(
                            'insert_date',
                            $attendance['insert_date']
                        )
                        ->exists();


                    if (!$notificationExists) {

                        $notification =
                            NotificationsModel::create([

                                'student_id' =>
                                    $student->id,

                                'halaqa_id' =>
                                    $student->halaqa_id,

                                'title' =>
                                    'إشعار غياب',

                                'notification_time' =>
                                    now()->format('H:i:s'),

                                'insert_date' =>
                                    $attendance['insert_date'],

                            ]);


                        $notifications[] = $notification;
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'message' =>
                    'تم إضافة التحضير بنجاح',

                'attendances' =>
                    $attendances,

                'notifications' =>
                    $notifications,

            ], 201);


        } catch (\Exception $e) {

            DB::rollBack();


            /*
            |--------------------------------------------------------------------------
            | خطأ التكرار
            |--------------------------------------------------------------------------
            */

            if (
                str_contains(
                    $e->getMessage(),
                    'تم تحضيره مسبقًا'
                ) ||
                str_contains(
                    $e->getMessage(),
                    'تم إرسال'
                )
            ) {

                return response()->json([

                    'message' =>
                        $e->getMessage(),

                ], 409);
            }


            /*
            |--------------------------------------------------------------------------
            | الأخطاء الأخرى
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'message' =>
                    'حدث خطأ أثناء إضافة التحضير',

                'error' =>
                    $e->getMessage()

            ], 500);
        }
    }


    // ____________________________________________________________
    // جلب جميع سجلات التحضير
    // ____________________________________________________________

    public function get_all_attendances_STD(GetAllAttendancesRequest $request)
    {

        $attendances = AttendancesModel::where('role','student')->get();

        return response()->json([
            'message' => 'تم جلب البيانات بنجاح',
            'attendances' => $attendances
        ], 200);
    }
     public function get_all_attendances_TCH(GetAllAttendancesRequest $request)
    {
        $attendances = AttendancesModel::where('role','teacher')->get();

        return response()->json([
            'message' => 'تم جلب البيانات بنجاح',
            'attendances' => $attendances
        ], 200);
    }


    // ____________________________________________________________
    // جلب إحصائيات تحضير مستخدم معين
    // ____________________________________________________________

    public function get_special_attendances(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);


        $present = AttendancesModel::where(
            'user_id',
            $request->user_id
        )
        ->where(
            'attendance_state',
            'present'
        )
        ->count();


        $absent = AttendancesModel::where(
            'user_id',
            $request->user_id
        )
        ->where(
            'attendance_state',
            'absent'
        )
        ->count();


        $late = AttendancesModel::where(
            'user_id',
            $request->user_id
        )
        ->where(
            'attendance_state',
            'late'
        )
        ->count();


        $total = AttendancesModel::where(
            'user_id',
            $request->user_id
        )->count();


        return response()->json([

            'message' =>
                'تم جلب البيانات بنجاح',

            'data' => [

                'user_id' =>
                    $request->user_id,

                'total_days' =>
                    $total,

                'present_days' =>
                    $present,

                'absent_days' =>
                    $absent,

                'late_days' =>
                    $late,

            ]

        ], 200);
    }


    // ____________________________________________________________
    // جلب الطلاب الغائبين حسب التاريخ
    // ____________________________________________________________

    public function getAbsentStudentsByDate(getAbsentStudentByDateRequest $request)
    {
        $request->validate([
            'insert_date' => 'required|date',
        ]);


        $absentStudents = AttendancesModel::with([
            'user.student.halaqa'
        ])
        ->whereDate(
            'insert_date',
            $request->insert_date
        )
        ->where(
            'role',
            'student'
        )
        ->where(
            'attendance_state',
            'absent'
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | لا يوجد طلاب غائبون
        |--------------------------------------------------------------------------
        */

        if ($absentStudents->isEmpty()) {

            return response()->json([

                'message' =>
                    'لا يوجد طلاب غائبون في هذا التاريخ.',

                'insert_date' =>
                    $request->insert_date,

                'count' =>
                    0,

                'students' =>
                    []

            ], 200);
        }


        /*
        |--------------------------------------------------------------------------
        | تجهيز بيانات الطلاب
        |--------------------------------------------------------------------------
        */

        $students = $absentStudents
            ->unique('user_id')
            ->map(function ($attendance) {

                return [

                    'attendance_id' =>
                        $attendance->id,

                    'user_id' =>
                        $attendance->user_id,

                    'student_name' =>
                        $attendance->user->name ?? null,

                    'halaqa_name' =>
                        $attendance->user
                            ->student
                            ->halaqa
                            ->halaqa_type ?? null,

                    'insert_date' =>
                        $attendance->insert_date,

                ];
            })
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'message' =>
                'تم جلب الطلاب الغائبين بنجاح.',

            'insert_date' =>
                $request->insert_date,

            'count' =>
                $students->count(),

            'students' =>
                $students

        ], 200);
    }
}

