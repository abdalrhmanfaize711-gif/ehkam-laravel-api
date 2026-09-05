<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddEtqanRecordRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateEtqanRecordRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\EtqanRecordModel;
use App\Models\NotsModel;
use App\Models\NotificationsModel;
use App\Models\StudentModel;
use App\Models\User;

use App\Services\QuranPageService;


class EtqanRecordController extends Controller
{
    /**
     * إضافة سجل أو عدة سجلات إتقان.
     *
     * num_of_sheets لا يتم أخذه من Flutter.
     *
     * يتم حسابه تلقائياً بواسطة QuranPageService
     * اعتماداً على:
     *
     * from_surah
     * from_ayah
     * to_surah
     * to_ayah
     *
     * ثم:
     *
     * num_of_sheets = ceil(num_of_pages / 2)
     */
    public function add_etqan_records(
        AddEtqanRecordRequest $request,
        QuranPageService $quranPageService
    ) {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | تحديد هل الطلب Record واحد أم عدة Records
            |--------------------------------------------------------------------------
            */

            $records = $request->has('records')
                ? $request->input('records')
                : [$request->all()];


            /*
            |--------------------------------------------------------------------------
            | التحقق من وجود Records
            |--------------------------------------------------------------------------
            */

            if (empty($records)) {

                DB::rollBack();

                return response()->json([
                    'message' => 'لم يتم إرسال أي سجل'
                ], 400);
            }


            /*
            |--------------------------------------------------------------------------
            | التحقق من student_id
            |--------------------------------------------------------------------------
            */

            $studentId = $records[0]['student_id'] ?? null;

            if (!$studentId) {

                DB::rollBack();

                return response()->json([
                    'message' => 'student_id مطلوب'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | جلب الطالب
            |--------------------------------------------------------------------------
            */

            $student = StudentModel::find($studentId);

            if (!$student) {

                DB::rollBack();

                return response()->json([
                    'message' => 'الطالب غير موجود'
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | التحقق من أن المستخدم طالب
            |--------------------------------------------------------------------------
            */

            $role = User::where(
                'id',
                $student->user_id
            )->value('role');


            if ($role !== 'student') {

                DB::rollBack();

                return response()->json([
                    'message' => 'ليس معرفاً كطالب'
                ], 400);
            }


            /*
            |--------------------------------------------------------------------------
            | هل الطلب مراجعة عامة فقط؟
            |--------------------------------------------------------------------------
            |
            | يحتوي على:
            |
            | student_id
            | general_revision
            |
            | فقط بدون بيانات الإتقان.
            |
            */

            $firstRecord = $records[0];

            $isRevisionOnly =
                isset($firstRecord['student_id']) &&

                array_key_exists(
                    'general_revision',
                    $firstRecord
                ) &&

                !isset($firstRecord['from_surah']) &&
                !isset($firstRecord['from_ayah']) &&
                !isset($firstRecord['to_surah']) &&
                !isset($firstRecord['to_ayah']) &&
                !isset($firstRecord['num_of_sheets']) &&
                !isset($firstRecord['memorization_state']);


            /*
            |--------------------------------------------------------------------------
            | حالة المراجعة فقط
            |--------------------------------------------------------------------------
            */

            if ($isRevisionOnly) {

                foreach ($records as $data) {

                    if (
                        ($data['general_revision'] ?? false) == false ||
                        ($data['general_revision'] ?? false) == 0
                    ) {

                        $notificationRequest = new Request([
                            'student_id' =>
                                $data['student_id'],
                        ]);


                        $this->createNotification(
                            $notificationRequest,
                            'إشعار ربط عام'
                        );
                    }
                }


                DB::commit();


                return response()->json([

                    'message' =>
                        'تم إضافة إشعار المراجعة بنجاح',

                    'count' =>
                        count($records),

                ], 201);
            }


            /*
            |--------------------------------------------------------------------------
            | إضافة سجلات الإتقان
            |--------------------------------------------------------------------------
            */

            $createdRecords = [];


            foreach ($records as $data) {

                /*
                |--------------------------------------------------------------------------
                | التحقق من student_id
                |--------------------------------------------------------------------------
                */

                $recordStudentId =
                    $data['student_id'] ?? null;


                if (!$recordStudentId) {

                    throw new \Exception(
                        'student_id مطلوب لكل سجل'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | التحقق من الطالب الخاص بالسجل
                |--------------------------------------------------------------------------
                */

                $recordStudent =
                    StudentModel::find($recordStudentId);


                if (!$recordStudent) {

                    throw new \Exception(
                        'الطالب غير موجود: ' .
                        $recordStudentId
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | بيانات القرآن
                |--------------------------------------------------------------------------
                */

                $fromSurah =
                    $data['from_surah'] ?? null;

                $fromAyah =
                    $data['from_ayah'] ?? null;

                $toSurah =
                    $data['to_surah'] ?? null;

                $toAyah =
                    $data['to_ayah'] ?? null;


                /*
                |--------------------------------------------------------------------------
                | التحقق من بيانات القرآن
                |--------------------------------------------------------------------------
                |
                | لا يمكن حساب الأوراق بدون الموضع الكامل.
                |
                */

                if (
                    $fromSurah === null ||
                    $fromAyah === null ||
                    $toSurah === null ||
                    $toAyah === null
                ) {

                    throw new \Exception(
                        'from_surah و from_ayah و to_surah و to_ayah مطلوبة لحساب عدد الأوراق'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | حساب عدد الصفحات
                |--------------------------------------------------------------------------
                |
                | QuranPageService يحسب عدد الصفحات
                | من موضع البداية إلى موضع النهاية.
                |
                */

                $numOfPages =
                    $quranPageService->calculatePages(
                        $fromSurah,
                        $fromAyah,
                        $toSurah,
                        $toAyah
                    );


                /*
                |--------------------------------------------------------------------------
                | تحويل الصفحات إلى أوراق
                |--------------------------------------------------------------------------
                |
                | الورقة = صفحتين.
                |
                | مثال:
                |
                | 1 صفحة  => 1 ورقة
                | 2 صفحات => 1 ورقة
                | 3 صفحات => 2 ورقة
                | 4 صفحات => 2 ورقة
                | 5 صفحات => 3 ورقة
                |
                */

                $numOfSheets =
                    (int) ceil($numOfPages / 2);


                /*
                |--------------------------------------------------------------------------
                | إنشاء سجل الإتقان
                |--------------------------------------------------------------------------
                */

                $record = EtqanRecordModel::create([

                    'student_id' =>
                        $recordStudentId,

                    'from_surah' =>
                        $fromSurah,

                    'from_ayah' =>
                        $fromAyah,

                    'to_surah' =>
                        $toSurah,

                    'to_ayah' =>
                        $toAyah,

                    /*
                    |--------------------------------------------------------------
                    | محسوبة تلقائياً
                    |--------------------------------------------------------------
                    */

                    'num_of_sheets' =>
                        $numOfSheets,

                    'memorization_state' =>
                        $data['memorization_state'] ?? null,

                    'general_revision' =>
                        $data['general_revision'] ?? false,

                    'addition_date' =>
                        $data['addition_date'] ?? null,

                ]);


                /*
                |--------------------------------------------------------------------------
                | إضافة السجل إلى Response
                |--------------------------------------------------------------------------
                */

                $createdRecords[] = $record;


                /*
                |--------------------------------------------------------------------------
                | Request خاص بهذا Record
                |--------------------------------------------------------------------------
                */

                $notificationRequest = new Request([
                    'student_id' =>
                        $recordStudentId,
                ]);


                /*
                |--------------------------------------------------------------------------
                | إشعار عدم الحفظ
                |--------------------------------------------------------------------------
                */

                if (
                    ($data['memorization_state'] ?? null)
                    == 'لم يحفظ'
                ) {

                    $this->createNotification(
                        $notificationRequest,
                        'إشعار إضافة'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | إشعار الربط العام
                |--------------------------------------------------------------------------
                */

                if (
                    ($data['general_revision'] ?? false)
                    == false
                    ||
                    ($data['general_revision'] ?? false)
                    == 0
                ) {

                    $this->createNotification(
                        $notificationRequest,
                        'إشعار ربط عام'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | فحص إكمال مرحلة الإتقان
            |--------------------------------------------------------------------------
            */

            $this->checkEtqanCompleted(
                new Request([
                    'student_id' =>
                        $student->id
                ])
            );


            /*
            |--------------------------------------------------------------------------
            | إضافة الملاحظة
            |--------------------------------------------------------------------------
            */

            $note = null;


            if (!empty($request->notes_text)) {

                $note = NotsModel::create([

                    'text_nots' =>
                        $request->notes_text,

                    'teacher_id' =>
                        $request->teacher_id,

                    'student_id' =>
                        $student->id,

                    'insert_date' =>
                        now(),

                ]);
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
                    count($createdRecords) === 1
                        ? 'تم إضافة سجل الإتقان بنجاح'
                        : 'تم إضافة سجلات الإتقان بنجاح',

                'count' =>
                    count($createdRecords),

                'records' =>
                    $createdRecords,

                'note' =>
                    $note,

            ], 201);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'message' =>
                    $e->getMessage()

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | فحص إكمال مرحلة الإتقان
    |--------------------------------------------------------------------------
    */

    private function checkEtqanCompleted($request)
    {
        /*
        |--------------------------------------------------------------------------
        | أول سجل
        |--------------------------------------------------------------------------
        */

        $firstRecord =
            EtqanRecordModel::where(
                'student_id',
                $request->student_id
            )
            ->oldest('id')
            ->first();


        /*
        |--------------------------------------------------------------------------
        | آخر سجل
        |--------------------------------------------------------------------------
        */

        $lastRecord =
            EtqanRecordModel::where(
                'student_id',
                $request->student_id
            )
            ->latest('id')
            ->first();


        if (!$firstRecord || !$lastRecord) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | التحقق من إكمال القرآن
        |--------------------------------------------------------------------------
        */

        $completed =

            (
                $firstRecord->from_surah == 'الناس'
                &&
                $firstRecord->from_ayah == 1
                &&
                $lastRecord->to_surah == 'البقرة'
            )

            ||

            (
                $firstRecord->from_surah == 'البقرة'
                &&
                $firstRecord->from_ayah == 1
                &&
                $lastRecord->to_surah == 'الناس'
            );


        if (!$completed) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | جلب الطالب
        |--------------------------------------------------------------------------
        */

        $student =
            StudentModel::find(
                $request->student_id
            );


        if (!$student) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | تحديد المرحلة التالية
        |--------------------------------------------------------------------------
        */

        switch ($student->stage) {

            case 'اتقان اول':

                $title = 'إكمال إتقان أول';

                $student->update([
                    'stage' => 'اتقان ثاني'
                ]);

                break;


            case 'اتقان ثاني':

                $title = 'إكمال إتقان ثاني';

                $student->update([
                    'stage' => 'اتقان ثالث'
                ]);

                break;


            case 'اتقان ثالث':

                $title = 'إكمال إتقان ثالث';

                break;


            default:

                return;
        }


        /*
        |--------------------------------------------------------------------------
        | إنشاء إشعار الإكمال
        |--------------------------------------------------------------------------
        */

        $this->createNotification(
            $request,
            $title
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء Notification
    |--------------------------------------------------------------------------
    */

    private function createNotification(
        $request,
        $title
    ) {
        /*
        |--------------------------------------------------------------------------
        | منع تكرار نفس الإشعار
        |--------------------------------------------------------------------------
        */

        $exists =
            NotificationsModel::where(
                'student_id',
                $request->student_id
            )
            ->where(
                'title',
                $title
            )
            ->exists();


        if ($exists) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | جلب الطالب
        |--------------------------------------------------------------------------
        */

        $student =
            StudentModel::find(
                $request->student_id
            );


        if (!$student) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | إنشاء الإشعار
        |--------------------------------------------------------------------------
        */

        NotificationsModel::create([

            'student_id' =>
                $student->id,

            'halaqa_id' =>
                $student->halaqa_id,

            'title' =>
                $title,

            'notification_time' =>
                now()->format('H:i:s'),

            'insert_date' =>
                now()->toDateString(),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | جلب جميع سجلات الإتقان
    |--------------------------------------------------------------------------
    */

    public function get_all_record()
    {
        $records =
            EtqanRecordModel::all();


        return response()->json([

            'message' =>
                'تم استرجاع السجلات بنجاح',

            'records' =>
                $records

        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | جلب سجل واحد
    |--------------------------------------------------------------------------
    */

    public function get_special_record(IdRequest $request)
    {
        $record =
            EtqanRecordModel::find(
                $request->id
            );


        if (!$record) {

            return response()->json([

                'message' =>
                    'لم يتم العثور على السجل'

            ], 404);
        }


        return response()->json([

            'message' =>
                'تم استرجاع السجل بنجاح',

            'record' =>
                $record

        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | تحديث سجل الإتقان
    |--------------------------------------------------------------------------
    |
    | مهم:
    | num_of_sheets لا يأتي من Flutter.
    |
    | يتم إعادة حسابه عند كل Update.
    |
    */

    public function update_record(
        UpdateEtqanRecordRequest $request,
        QuranPageService $quranPageService
    ) {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | جلب السجل
            |--------------------------------------------------------------------------
            */

            $record =
                EtqanRecordModel::find(
                    $request->id
                );


            if (!$record) {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'لم يتم العثور على السجل'

                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | جلب الطالب
            |--------------------------------------------------------------------------
            */

            $student =
                StudentModel::find(
                    $request->student_id
                );


            if (!$student) {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'لم يتم العثور على الطالب'

                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | التحقق من أن المستخدم طالب
            |--------------------------------------------------------------------------
            */

            $role =
                User::where(
                    'id',
                    $student->user_id
                )
                ->value('role');


            if ($role !== 'student') {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'ليس معرفاً كطالب'

                ], 400);
            }


            /*
            |--------------------------------------------------------------------------
            | بيانات القرآن الجديدة
            |--------------------------------------------------------------------------
            */

            $fromSurah =
                $request->from_surah;

            $fromAyah =
                $request->from_ayah;

            $toSurah =
                $request->to_surah;

            $toAyah =
                $request->to_ayah;


            /*
            |--------------------------------------------------------------------------
            | التحقق من البيانات
            |--------------------------------------------------------------------------
            */

            if (
                $fromSurah === null ||
                $fromAyah === null ||
                $toSurah === null ||
                $toAyah === null
            ) {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'from_surah و from_ayah و to_surah و to_ayah مطلوبة لحساب عدد الأوراق'

                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | إعادة حساب الصفحات
            |--------------------------------------------------------------------------
            */

            $numOfPages =
                $quranPageService->calculatePages(
                    $fromSurah,
                    $fromAyah,
                    $toSurah,
                    $toAyah
                );


            /*
            |--------------------------------------------------------------------------
            | تحويل الصفحات إلى أوراق
            |--------------------------------------------------------------------------
            */

            $numOfSheets =
                (int) ceil($numOfPages / 2);


            /*
            |--------------------------------------------------------------------------
            | تحديث السجل
            |--------------------------------------------------------------------------
            */

            $record->update([

                'student_id' =>
                    $request->student_id,

                'from_surah' =>
                    $fromSurah,

                'from_ayah' =>
                    $fromAyah,

                'to_surah' =>
                    $toSurah,

                'to_ayah' =>
                    $toAyah,

                /*
                |--------------------------------------------------------------
                | محسوبة تلقائياً
                |--------------------------------------------------------------
                */

                'num_of_sheets' =>
                    $numOfSheets,

                'memorization_state' =>
                    $request->memorization_state,

                'general_revision' =>
                    $request->general_revision,

                'addition_date' =>
                    $request->addition_date,

            ]);


            /*
            |--------------------------------------------------------------------------
            | إضافة ملاحظة
            |--------------------------------------------------------------------------
            */

            $note = null;


            if (!empty($request->notes_text)) {

                $note = NotsModel::create([

                    'text_nots' =>
                        $request->notes_text,

                    'teacher_id' =>
                        $request->teacher_id,

                    'student_id' =>
                        $request->student_id,

                    'insert_date' =>
                        now(),

                ]);
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
                    'تم تحديث سجل الإتقان بنجاح',

                'record' =>
                    $record->fresh(),

                'note' =>
                    $note,

            ], 200);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'message' =>
                    $e->getMessage()

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | حذف سجل الإتقان
    |--------------------------------------------------------------------------
    */

    public function delete(IdRequest $request)
    {
        DB::beginTransaction();

        try {

            $record =
                EtqanRecordModel::find(
                    $request->id
                );


            if (!$record) {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'السجل غير موجود'

                ], 404);
            }


            $record->delete();


            DB::commit();


            return response()->json([

                'message' =>
                    'تم حذف السجل بنجاح'

            ], 200);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'message' =>
                    $e->getMessage()

            ], 500);
        }
    }
}