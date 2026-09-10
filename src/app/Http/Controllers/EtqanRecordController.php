<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddEtqanRecordRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateEtqanRecordRequest;
use App\Http\Requests\Api\UpdateStudentRecordRequest;
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
     * الخطوات:
     *
     * 1. calculatePages()
     * 2. calculateSheets()
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
                !isset($firstRecord['memorization_state']) &&
                !isset($firstRecord['total_mistakes']);

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
                | التحقق من الطالب
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
                | التحقق من Role
                |--------------------------------------------------------------------------
                */

                $recordRole = User::where(
                    'id',
                    $recordStudent->user_id
                )->value('role');


                if ($recordRole !== 'student') {

                    throw new \Exception(
                        'المستخدم المرتبط ليس طالباً: ' .
                        $recordStudentId
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | منع إرسال num_of_sheets من Flutter
                |--------------------------------------------------------------------------
                */

                if (array_key_exists('num_of_sheets', $data)) {

                    throw new \InvalidArgumentException(
                        'num_of_sheets يتم حسابه تلقائياً ولا يمكن إرساله من Flutter'
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
                */

                if (
                    $fromSurah === null ||
                    $fromAyah === null ||
                    $toSurah === null ||
                    $toAyah === null
                ) {

                    throw new \InvalidArgumentException(
                        'from_surah و from_ayah و to_surah و to_ayah مطلوبة لحساب عدد الأوراق'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | حساب عدد الصفحات
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
                | حساب عدد الأوراق
                |--------------------------------------------------------------------------
                |
                | يتم الحساب بواسطة Service.
                |
                | لا نستخدم ceil() داخل Controller.
                |
                */

                $numOfSheets =
                    $quranPageService->calculateSheets(
                        $numOfPages
                    );


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
                    |--------------------------------------------------------------------------
                    | محسوبة تلقائياً
                    |--------------------------------------------------------------------------
                    */

                    'num_of_sheets' =>
                        $numOfSheets,

                    'memorization_state' =>
                        $data['memorization_state'] ?? null,
                    'total_mistakes' =>
                        $data['total_mistakes'] ?? null,

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
        |
        | مهم:
        | استخدمنا نفس أسماء المراحل الموجودة في
        | update_record_of_STD()
        |
        */

        switch (trim($student->stage)) {

            case 'إتقان أول':

                $title = 'إكمال إتقان أول';

                $student->update([
                    'stage' => 'إتقان ثاني'
                ]);

                break;


            case 'إتقان ثاني':

                $title = 'إكمال إتقان ثاني';

                $student->update([
                    'stage' => 'إتقان ثالث'
                ]);

                break;


            case 'إتقان ثالث':

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
    |
    | Flutter لا يرسل num_of_sheets.
    |
    | إذا تغيرت بيانات القرآن يتم:
    |
    | 1. calculatePages()
    | 2. calculateSheets()
    |
    | أما إذا تم تعديل حقل آخر فقط،
    | لا نعيد الحساب بدون داعٍ.
    |
    */

   public function update_etqan_record(
    UpdateStudentRecordRequest $request,
    QuranPageService $quranPageService
) {
    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Get Student
        |--------------------------------------------------------------------------
        */

        $student = StudentModel::find($request->student_id);

        if (!$student) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الطالب',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Verify User Role
        |--------------------------------------------------------------------------
        */

        $role = User::where(
            'id',
            $student->user_id
        )->value('role');

        if ($role !== 'student') {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'المستخدم المرتبط ليس طالباً',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Stage
        |--------------------------------------------------------------------------
        */

        $etqanStages = [
            'إتقان أول',
            'إتقان ثاني',
            'إتقان ثالث',
        ];

        $stage = trim($student->stage);

        if (!in_array($stage, $etqanStages, true)) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'الطالب ليس في مرحلة الإتقان',
                'stage' => $student->stage,
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Reject Addition-only Fields
        |--------------------------------------------------------------------------
        */

        $additionOnlyFields = [
            'repeated_times',
            'daily_revision',
        ];

        foreach ($additionOnlyFields as $field) {

            if ($request->has($field)) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                        "الحقل {$field} غير مسموح به في سجل الإتقان",
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Reject num_of_sheets
        |--------------------------------------------------------------------------
        */

        if ($request->has('num_of_sheets')) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>
                    'الحقل num_of_sheets يتم حسابه تلقائياً ولا يمكن تعديله يدوياً',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Reject num_of_pages
        |--------------------------------------------------------------------------
        */

        if ($request->has('num_of_pages')) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>
                    'الحقل num_of_pages غير مسموح به في سجل الإتقان',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Latest Etqan Record
        |--------------------------------------------------------------------------
        */

        $record = EtqanRecordModel::where(
            'student_id',
            $student->id
        )
            ->orderByDesc('addition_date')
            ->first();

        if (!$record) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>
                    'لم يتم العثور على سجل الإتقان لهذا الطالب',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare Update Data
        |--------------------------------------------------------------------------
        */

        $updateData = [];

        /*
        |--------------------------------------------------------------------------
        | Common Fields
        |--------------------------------------------------------------------------
        */

        $commonFields = [
            'from_surah',
            'from_ayah',
            'to_surah',
            'to_ayah',
            'total_mistakes',
            'memorization_state',
            'addition_date',
            'general_revision',
        ];

        foreach ($commonFields as $field) {

            if ($request->has($field)) {

                $updateData[$field] =
                    $request->input($field);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Etqan-only Fields
        |--------------------------------------------------------------------------
        */

        if ($request->has('total_mistakes')) {

            $updateData['total_mistakes'] =
                $request->input('total_mistakes');
        }

        /*
        |--------------------------------------------------------------------------
        | Check Quran Fields
        |--------------------------------------------------------------------------
        */

        $quranFieldChanged =
            $request->has('from_surah') ||
            $request->has('from_ayah') ||
            $request->has('to_surah') ||
            $request->has('to_ayah');

        /*
        |--------------------------------------------------------------------------
        | Recalculate Pages + Sheets
        |--------------------------------------------------------------------------
        */

        if ($quranFieldChanged) {

            $fromSurah = $request->has('from_surah')
                ? $request->input('from_surah')
                : $record->from_surah;

            $fromAyah = $request->has('from_ayah')
                ? $request->input('from_ayah')
                : $record->from_ayah;

            $toSurah = $request->has('to_surah')
                ? $request->input('to_surah')
                : $record->to_surah;

            $toAyah = $request->has('to_ayah')
                ? $request->input('to_ayah')
                : $record->to_ayah;

            /*
            |--------------------------------------------------------------------------
            | Validate Quran Range
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
                    'success' => false,
                    'message' =>
                        'يجب تحديد بداية ونهاية الحفظ كاملة لحساب عدد الأوراق',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Pages
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
            | Calculate Sheets
            |--------------------------------------------------------------------------
            */

            $numOfSheets =
                $quranPageService->calculateSheets(
                    $numOfPages
                );

            /*
            |--------------------------------------------------------------------------
            | Save Calculated Sheets
            |--------------------------------------------------------------------------
            */

            $updateData['num_of_sheets'] =
                $numOfSheets;
        }

        /*
        |--------------------------------------------------------------------------
        | Check Update Data
        |--------------------------------------------------------------------------
        */

        if (empty($updateData)) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>
                    'لم يتم إرسال أي بيانات لتحديث السجل',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Record
        |--------------------------------------------------------------------------
        */

        $record->update($updateData);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سجل الإتقان بنجاح',
            'record_type' => 'etqan',
            'stage' => $stage,
            'student_id' => $student->id,
            'record' => $record->fresh(),
        ], 200);

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث سجل الإتقان',
            'error' => $e->getMessage(),
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