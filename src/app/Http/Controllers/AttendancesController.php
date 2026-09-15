<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\GetAllAttendancesRequest;
use App\Http\Requests\Api\GetSpecialAttendancesRequest;
use App\Http\Requests\Api\getAbsentStudentByDateRequest;
use App\Http\Requests\Api\AddLateAttendanceRequest;
use App\Http\Requests\Api\AddAttendancesRequest;
use App\Http\Requests\Api\GetTodayAttendancePercentageRequest;
use App\Http\Requests\Api\GetLastFourAttendanceDatesRequest;

use App\Http\Requests\Api\IDStudent;
use App\Models\AttendancesModel;
use App\Models\NotificationsModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AttendancesController extends Controller
{

    // ____________________________________________________________
    // نسبة الحضور اليوم
    // ____________________________________________________________

    public function getTodayAttendancePercentage() {
        /*
        |--------------------------------------------------------------------------
        | إجمالي المستخدمين
        |--------------------------------------------------------------------------
        |
        | نريد الطلاب + المعلمين فقط.
        |
        | لا ندخل admin في حساب نسبة الحضور.
        |
        */

        $totalUsers = DB::table('users')
            ->whereIn('role', ['student', 'teacher'])
            ->count();


        /*
        |--------------------------------------------------------------------------
        | الحاضرون اليوم
        |--------------------------------------------------------------------------
        |
        | present + late يعتبران حضورًا.
        |
        */

        $presentUsers = AttendancesModel::whereDate(
                'insert_date',
                today()
            )
            ->whereIn(
                'role',
                ['student', 'teacher']
            )
            ->whereIn(
                'attendance_state',
                ['present', 'late']
            )
            ->distinct('user_id')
            ->count('user_id');


        /*
        |--------------------------------------------------------------------------
        | نسبة الحضور
        |--------------------------------------------------------------------------
        */

        $attendancePercentage = $totalUsers > 0
            ? round(($presentUsers / $totalUsers) * 100)
            : 0;


        /*
        |--------------------------------------------------------------------------
        | الغائبون
        |--------------------------------------------------------------------------
        */

        $absentUsers = max(
            0,
            $totalUsers - $presentUsers
        );


        return response()->json([

            'success' => true,

            'message' =>
                'تم استرجاع نسبة الحضور اليوم بنجاح.',

            'data' => [

                'attendance_percentage' =>
                    $attendancePercentage,

                'total_users' =>
                    $totalUsers,

                'present_users' =>
                    $presentUsers,

                'absent_users' =>
                    $absentUsers,

            ]

        ], 200);
    }


    // ____________________________________________________________
    // آخر 4 تواريخ تم فيها تسجيل التحضير للطلاب
    // ____________________________________________________________

    public function getLastFourAttendanceDates( ) {

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

            'dates' =>
                $dates

        ], 200);
    }


    // ____________________________________________________________
    // جلب الطلاب الغائبين حسب التاريخ
    // ____________________________________________________________

    public function getAbsentStudentByDate(
        getAbsentStudentByDateRequest $request
    ) {

        /*
        |--------------------------------------------------------------------------
        | استخدام insert_date
        |--------------------------------------------------------------------------
        |
        | كان الكود القديم يتحقق من date
        | ثم يستخدم insert_date.
        |
        | الآن نستخدم نفس الحقل في كل مكان.
        |
        */

        $request->validate([
            'insert_date' => 'required|date',
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

            'date' =>
                $request->insert_date,

            'count' =>
                $students->count(),

            'students' =>
                $students

        ], 200);
    }


    // ____________________________________________________________
    // إضافة تأخر لطالب
    // ____________________________________________________________

    public function addLateAttendance(
        AddLateAttendanceRequest $request
    ) {

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | استقبال البيانات
            |--------------------------------------------------------------------------
            */

            $userId =
                $request->user_id;

            $attendanceState =
                $request->attendance_state;


            /*
            |--------------------------------------------------------------------------
            | التأكد أن الحالة late
            |--------------------------------------------------------------------------
            */

            if ($attendanceState !== 'late') {

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' =>
                        'attendance_state يجب أن تكون late'

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

                    'success' => false,

                    'message' =>
                        'لا يوجد طالب مرتبط بهذا المستخدم'

                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | الحصول على آخر سجل حضور للطالب
            |--------------------------------------------------------------------------
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
            | تحديد التاريخ
            |--------------------------------------------------------------------------
            */

            $lastDate = $lastAttendance
                ? $lastAttendance->insert_date
                : now()->format('Y-m-d');


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
                ->orderByDesc('id')
                ->first();


            /*
            |--------------------------------------------------------------------------
            | يوجد سجل في آخر تاريخ
            |--------------------------------------------------------------------------
            */

            if ($existingAttendance) {

                /*
                |--------------------------------------------------------------------------
                | present -> late
                |--------------------------------------------------------------------------
                |
                | هذا غير مسموح.
                |
                | الكود القديم كان يرجع 200 برسالة:
                | تم تحويل سجل الغياب...
                |
                | رغم أنه لم يغير أي شيء.
                |
                */

                if (
                    $existingAttendance->attendance_state === 'present'
                ) {

                    DB::rollBack();

                    return response()->json([

                        'success' => false,

                        'message' =>
                            'الطالب مسجل حاضرًا في آخر تاريخ، ولا يمكن تحويل الحضور إلى تأخر',

                        'action' =>
                            'already_present',

                        'last_date' =>
                            $lastDate,

                        'attendance' =>
                            $existingAttendance,

                    ], 409);
                }


                /*
                |--------------------------------------------------------------------------
                | absent -> late
                |--------------------------------------------------------------------------
                */

                if (
                    $existingAttendance->attendance_state === 'absent'
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | تعديل نفس السجل
                    |--------------------------------------------------------------------------
                    */

                    $existingAttendance->update([

                        'attendance_state' =>
                            'late',

                    ]);


                    $existingAttendance->refresh();


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
                        $lastDate
                    )
                    ->delete();


                    /*
                    |--------------------------------------------------------------------------
                    | حساب مرات التأخر
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
                    | 3 مرات تأخر
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

                        'success' => true,

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
                | late موجود أصلًا
                |--------------------------------------------------------------------------
                */

                if (
                    $existingAttendance->attendance_state === 'late'
                ) {

                    DB::rollBack();

                    return response()->json([

                        'success' => false,

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

                    'success' => false,

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
            | لا يوجد سجل سابق
            |--------------------------------------------------------------------------
            |
            | ننشئ late جديد.
            |
            */

            $newAttendance =
                AttendancesModel::create([

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
            | حساب مرات التأخر
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
            | 3 مرات تأخر
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

                'success' => true,

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


        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

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

    public function add_attendances(
        AddAttendancesRequest $request
    ) {

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | التأكد من وجود البيانات
            |--------------------------------------------------------------------------
            */

            if (
                !$request->has('attendances') ||
                empty($request->attendances)
            ) {

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' =>
                        'لا توجد معلومات للحضور'

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
                        ['student', 'teacher'],
                        true
                    )
                ) {

                    throw new \Exception(
                        'role يجب أن يكون student أو teacher'
                    );
                }


                if (
                    !in_array(
                        $attendance['attendance_state'],
                        ['present', 'absent', 'late'],
                        true
                    )
                ) {

                    throw new \Exception(
                        'attendance_state يجب أن تكون present أو absent أو late'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | التأكد من وجود المستخدم
                |--------------------------------------------------------------------------
                */

                $userExists = DB::table('users')
                    ->where(
                        'id',
                        $attendance['user_id']
                    )
                    ->exists();


                if (!$userExists) {

                    throw new \Exception(
                        "المستخدم رقم {$attendance['user_id']} غير موجود"
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | التأكد من وجود الطالب/المعلم
                |--------------------------------------------------------------------------
                */

                if (
                    $attendance['role'] === 'student'
                ) {

                    $studentExists =
                        StudentModel::where(
                            'user_id',
                            $attendance['user_id']
                        )->exists();


                    if (!$studentExists) {

                        throw new \Exception(
                            'لا يوجد طالب مرتبط بهذا المستخدم'
                        );
                    }
                }


                if (
                    $attendance['role'] === 'teacher'
                ) {

                    $teacherExists =
                        TeacherModel::where(
                            'user_id',
                            $attendance['user_id']
                        )->exists();


                    if (!$teacherExists) {

                        throw new \Exception(
                            'لا يوجد معلم مرتبط بهذا المستخدم'
                        );
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | فحص التكرار في قاعدة البيانات
            |--------------------------------------------------------------------------
            */

            foreach ($request->attendances as $attendance) {

                $existingAttendance =
                    AttendancesModel::where(
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
                    | يسمح به لأننا سنعدل نفس السجل.
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


                if (
                    in_array(
                        $key,
                        $checkedUsers,
                        true
                    )
                ) {

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
                */

                if (
                    $attendance['role'] === 'student' &&
                    $attendance['attendance_state'] === 'late'
                ) {

                    $existingAttendance =
                        AttendancesModel::where(
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
                        | تعديل نفس السجل
                        |--------------------------------------------------------------------------
                        */

                        $existingAttendance->update([

                            'attendance_state' =>
                                'late',

                        ]);


                        $existingAttendance->refresh();


                        $attendances[] =
                            $existingAttendance;


                        /*
                        |--------------------------------------------------------------------------
                        | الطالب
                        |--------------------------------------------------------------------------
                        */

                        $student =
                            StudentModel::where(
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

                        $lateCount =
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
                                ->count();


                        /*
                        |--------------------------------------------------------------------------
                        | 3 مرات تأخر
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


                            $notifications[] =
                                $notification;


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
                        | مهم:
                        |
                        | لا نصل إلى create()
                        |--------------------------------------------------------------------------
                        */

                        continue;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | إنشاء سجل التحضير
                |--------------------------------------------------------------------------
                */

                $attendanceRecord =
                    AttendancesModel::create([

                        'user_id' =>
                            $attendance['user_id'],

                        'role' =>
                            $attendance['role'],

                        'attendance_state' =>
                            $attendance['attendance_state'],

                        'insert_date' =>
                            $attendance['insert_date'],

                    ]);


                $attendances[] =
                    $attendanceRecord;


                /*
                |--------------------------------------------------------------------------
                | معالجة late الجديد
                |--------------------------------------------------------------------------
                */

                if (
                    $attendance['role'] === 'student' &&
                    $attendance['attendance_state'] === 'late'
                ) {

                    $student =
                        StudentModel::where(
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
                    | حساب مرات التأخر
                    |--------------------------------------------------------------------------
                    */

                    $lateCount =
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
                            ->count();


                    /*
                    |--------------------------------------------------------------------------
                    | 3 مرات تأخر
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


                        $notifications[] =
                            $notification;


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

                    $student =
                        StudentModel::where(
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


                        $notifications[] =
                            $notification;
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

                'success' => true,

                'message' =>
                    'تم إضافة التحضير بنجاح',

                'attendances' =>
                    $attendances,

                'notifications' =>
                    $notifications,

            ], 201);


        } catch (\Throwable $e) {

            DB::rollBack();


            /*
            |--------------------------------------------------------------------------
            | أخطاء التكرار
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

                    'success' => false,

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

                'success' => false,

                'message' =>
                    'حدث خطأ أثناء إضافة التحضير',

                'error' =>
                    $e->getMessage()

            ], 500);
        }
    }


    // ____________________________________________________________
    // جلب جميع سجلات التحضير للطلاب
    // ____________________________________________________________

    public function get_all_attendances_STD(
        GetAllAttendancesRequest $request
    ) {

        $attendances =
            AttendancesModel::where(
                'role',
                'student'
            )->get();


        return response()->json([

            'message' =>
                'تم جلب البيانات بنجاح',

            'attendances' =>
                $attendances

        ], 200);
    }


    // ____________________________________________________________
    // جلب جميع سجلات التحضير للمعلمين
    // ____________________________________________________________

    public function get_all_attendances_TCH(
        GetAllAttendancesRequest $request
    ) {

        $attendances =
            AttendancesModel::where(
                'role',
                'teacher'
            )->get();


        return response()->json([

            'message' =>
                'تم جلب البيانات بنجاح',

            'attendances' =>
                $attendances

        ], 200);
    }


    // ____________________________________________________________
    // جلب إحصائيات تحضير مستخدم معين
    // ____________________________________________________________

    public function get_special_attendances(
        GetSpecialAttendancesRequest $request
    ) {

        /*
        |--------------------------------------------------------------------------
        | user_id
        |--------------------------------------------------------------------------
        */

        $userId =
            $request->user_id;


        /*
        |--------------------------------------------------------------------------
        | الحضور
        |--------------------------------------------------------------------------
        */

        $present =
            AttendancesModel::where(
                'user_id',
                $userId
            )
            ->where(
                'attendance_state',
                'present'
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | الغياب
        |--------------------------------------------------------------------------
        */

        $absent =
            AttendancesModel::where(
                'user_id',
                $userId
            )
            ->where(
                'attendance_state',
                'absent'
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | التأخر
        |--------------------------------------------------------------------------
        */

        $late =
            AttendancesModel::where(
                'user_id',
                $userId
            )
            ->where(
                'attendance_state',
                'late'
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | الإجمالي
        |--------------------------------------------------------------------------
        */

        $total =
            AttendancesModel::where(
                'user_id',
                $userId
            )->count();


        return response()->json([

            'message' =>
                'تم جلب البيانات بنجاح',

            'data' => [

                'user_id' =>
                    $userId,

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

    public function getAbsentStudentsByDate(
        getAbsentStudentByDateRequest $request
    ) {

        /*
        |--------------------------------------------------------------------------
        | التحقق من التاريخ
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'insert_date' =>
                'required|date',

        ]);


        /*
        |--------------------------------------------------------------------------
        | جلب سجلات الغياب
        |--------------------------------------------------------------------------
        */

        $absentStudents =
            AttendancesModel::with([

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

        $students =
            $absentStudents
                ->unique('user_id')
                ->map(function ($attendance) {

                    $user =
                        $attendance->user;

                    $student =
                        $user?->student;

                    $halaqa =
                        $student?->halaqa;


                    return [

                        'attendance_id' =>
                            $attendance->id,

                        'user_id' =>
                            $attendance->user_id,

                        'student_name' =>
                            $user?->name,

                        'halaqa_name' =>
                            $halaqa?->halaqa_type,

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

   public function getStudentAttendancePercentage(IDStudent $request)
{
    $studentId = $request->student_id;

    $student = StudentModel::findOrFail($studentId);

    $userId = $student->user_id;

    $query = AttendancesModel::where('user_id', $userId)
        ->where('role', 'student');

    $total = $query->count();

    $present = (clone $query)
        ->where('attendance_state', 'present')
        ->count();

    $late = (clone $query)
        ->where('attendance_state', 'late')
        ->count();

    $absent = (clone $query)
        ->where('attendance_state', 'absent')
        ->count();

    $attended = $present + $late;

    $percentage = $total > 0
        ? round(($attended / $total) * 100, 2)
        : 0;

    return response()->json([
        'student_id' => $studentId,
        'user_id' => $userId,
        'attendance_percentage' => $percentage,
        'total_days' => $total,
        'present_days' => $present,
        'late_days' => $late,
        'absent_days' => $absent,
    ]);
}

}