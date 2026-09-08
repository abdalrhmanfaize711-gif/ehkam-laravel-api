<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateAdditionRecordRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\AdditionRecordsModel;
use App\Models\NotsModel;
use App\Models\StudentModel;
use App\Models\NotificationsModel;
use App\Models\User;
use App\Http\Requests\Api\AddtionRecordRequest;
use MohamedSabil83\LaravelHijrian\Facades\Hijrian;
use App\Services\QuranPageService;


class AdditionRecordsController extends Controller
{
    /**
     * إضافة سجل أو عدة سجلات حفظ.
     *
     * num_of_pages لا يتم أخذه من Flutter.
     * يتم حسابه تلقائياً بواسطة QuranPageService
     * اعتماداً على:
     *
     * from_surah
     * from_ayah
     * to_surah
     * to_ayah
     */
    public function add_addition_records(
        AddtionRecordRequest $request,
        User $user,
        StudentModel $student,
        QuranPageService $quranPageService
    ) {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | تحديد هل الطلب Record واحد أم أكثر من Record
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
                    'message' => 'لايوجد طالب'
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | التحقق من أن المستخدم طالب
            |--------------------------------------------------------------------------
            */

            $role = User::where('id', $student->user_id)
                ->value('role');

            if ($role !== 'student') {

                DB::rollBack();

                return response()->json([
                    'message' => 'ليس معرفاً كطالب'
                ], 400);
            }


            /*
            |--------------------------------------------------------------------------
            | تحديد هل الطلب مراجعة فقط
            |--------------------------------------------------------------------------
            */

            $firstRecord = $records[0];

            $isRevisionOnly =
                isset($firstRecord['student_id']) &&
                array_key_exists('general_revision', $firstRecord) &&
                array_key_exists('daily_revision', $firstRecord) &&

                !isset($firstRecord['num_of_pages']) &&
                !isset($firstRecord['from_surah']) &&
                !isset($firstRecord['from_ayah']) &&
                !isset($firstRecord['to_surah']) &&
                !isset($firstRecord['to_ayah']) &&
                !isset($firstRecord['memorization_state']);


            /*
            |--------------------------------------------------------------------------
            | حالة المراجعة فقط
            |--------------------------------------------------------------------------
            */

            if ($isRevisionOnly) {

                foreach ($records as $data) {

                    if (
                        ($data['general_revision'] ?? false) == 0 ||
                        ($data['general_revision'] ?? false) == false
                    ) {

                        $notificationRequest = new Request([
                            'student_id' => $data['student_id'],
                        ]);

                        $this->createNotification(
                            $notificationRequest,
                            'إشعار ربط عام'
                        );
                    }
                }


                DB::commit();

                return response()->json([

                    'message' => 'تم إضافة إشعارات المراجعة بنجاح',

                    'count' => count($records),

                ], 201);
            }


            /*
            |--------------------------------------------------------------------------
            | إضافة سجلات الحفظ
            |--------------------------------------------------------------------------
            */

            $createdRecords = [];


            foreach ($records as $data) {

                /*
                |--------------------------------------------------------------------------
                | student_id الخاص بالسجل
                |--------------------------------------------------------------------------
                */

                $recordStudentId = $data['student_id'] ?? null;

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

                $recordStudent = StudentModel::find($recordStudentId);

                if (!$recordStudent) {

                    throw new \Exception(
                        'الطالب غير موجود: ' . $recordStudentId
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | التحقق من أن المستخدم المرتبط طالب
                |--------------------------------------------------------------------------
                */

                $recordRole = User::where(
                    'id',
                    $recordStudent->user_id
                )->value('role');

                if ($recordRole !== 'student') {

                    throw new \Exception(
                        'المستخدم المرتبط ليس طالباً: ' . $recordStudentId
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | بيانات القرآن
                |--------------------------------------------------------------------------
                */

                $fromSurah = $data['from_surah'] ?? null;
                $fromAyah  = $data['from_ayah'] ?? null;
                $toSurah   = $data['to_surah'] ?? null;
                $toAyah    = $data['to_ayah'] ?? null;


                /*
                |--------------------------------------------------------------------------
                | التحقق من اكتمال بيانات القرآن
                |--------------------------------------------------------------------------
                */

                if (
                    $fromSurah === null ||
                    $fromAyah === null ||
                    $toSurah === null ||
                    $toAyah === null
                ) {

                    throw new \InvalidArgumentException(
                        'يجب إرسال from_surah و from_ayah و to_surah و to_ayah'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | منع Flutter من إرسال num_of_pages
                |--------------------------------------------------------------------------
                */

                if (array_key_exists('num_of_pages', $data)) {

                    throw new \InvalidArgumentException(
                        'num_of_pages يتم حسابه تلقائياً ولا يمكن إرساله من Flutter'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | حساب عدد الصفحات
                |--------------------------------------------------------------------------
                */

                $numOfPages = $quranPageService->calculatePages(
                    $fromSurah,
                    $fromAyah,
                    $toSurah,
                    $toAyah
                );


                /*
                |--------------------------------------------------------------------------
                | إنشاء سجل الإضافة
                |--------------------------------------------------------------------------
                */

                $record = AdditionRecordsModel::create([

                    'student_id' =>
                        $recordStudentId,

                    /*
                    |--------------------------------------------------------------------------
                    | محسوبة بواسطة QuranPageService
                    |--------------------------------------------------------------------------
                    */

                    'num_of_pages' =>
                        $numOfPages,

                    'from_surah' =>
                        $fromSurah,

                    'from_ayah' =>
                        $fromAyah,

                    'to_surah' =>
                        $toSurah,

                    'to_ayah' =>
                        $toAyah,

                    'repeated_times' =>
                        $data['repeated_times'] ?? null,

                    'memorization_state' =>
                        $data['memorization_state'] ?? null,

                    'addition_date' =>
                        $data['addition_date'] ?? null,

                    'general_revision' =>
                        $data['general_revision'] ?? false,

                    'daily_revision' =>
                        $data['daily_revision'] ?? false,

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
                    'student_id' => $recordStudentId,
                ]);


                /*
                |--------------------------------------------------------------------------
                | إشعار عدم الحفظ / الإضافة
                |--------------------------------------------------------------------------
                */

                if (
                    ($data['memorization_state'] ?? null) == 'لم يحفظ' ||
                    ($data['daily_revision'] ?? false) == 0 ||
                    ($data['daily_revision'] ?? false) == false
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
                    ($data['general_revision'] ?? false) == 0 ||
                    ($data['general_revision'] ?? false) == false
                ) {

                    $this->createNotification(
                        $notificationRequest,
                        'إشعار ربط عام'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | فحص إكمال المرحلة
            |--------------------------------------------------------------------------
            */

            $this->IsCompleated($student->id);


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
                        ? 'تم إضافة السجل بنجاح'
                        : 'تم إضافة السجلات بنجاح',

                'count' =>
                    count($createdRecords),

                'addition_records' =>
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
    | فحص إكمال مرحلة الإضافة
    |--------------------------------------------------------------------------
    */

    public function IsCompleated(int $student_id)
    {
        $firstRecord = AdditionRecordsModel::where(
            'student_id',
            $student_id
        )
            ->oldest('id')
            ->first();


        $lastRecord = AdditionRecordsModel::where(
            'student_id',
            $student_id
        )
            ->latest('id')
            ->first();


        if (!$firstRecord || !$lastRecord) {
            return;
        }


        $completed =
            (
                $firstRecord->from_surah == 'الناس' &&
                $lastRecord->to_surah == 'البقرة'
            )
            ||
            (
                $firstRecord->from_surah == 'البقرة' &&
                $lastRecord->to_surah == 'الناس'
            );


        if ($completed) {

            $exists = NotificationsModel::where(
                'student_id',
                $student_id
            )
                ->where(
                    'title',
                    'إشعار إكمال مرحلة الإضافة'
                )
                ->exists();


            if (!$exists) {

                $halaqa_id = StudentModel::where(
                    'id',
                    $student_id
                )
                    ->value('halaqa_id');


                NotificationsModel::create([

                    'student_id' =>
                        $student_id,

                    'halaqa_id' =>
                        $halaqa_id,

                    'title' =>
                        'إشعار إكمال مرحلة الإضافة',

                    'notification_time' =>
                        now()->format('G:i:s'),

                    'insert_date' =>
                        now()->toDateString()

                ]);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء Notification
    |--------------------------------------------------------------------------
    */

    private function createNotification($request, $title)
    {
        $student = StudentModel::find(
            $request->student_id
        );


        if (!$student) {

            throw new \Exception(
                'الطالب غير موجود'
            );
        }


        NotificationsModel::create([

            'student_id' =>
                $student->id,

            'halaqa_id' =>
                $student->halaqa_id,

            'title' =>
                $title,

            'notification_time' =>
                now()->format('G:i:s'),

            'insert_date' =>
                now()->toDateString()

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Get All Records
    |--------------------------------------------------------------------------
    */

    public function get_all_record()
    {
        $records = AdditionRecordsModel::all();


        return response()->json([

            'message' =>
                'تم استرجاع السجلات بنجاح',

            'records' =>
                $records

        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Special Record
    |--------------------------------------------------------------------------
    */

    public function get_special_record(IdRequest $request)
    {
        $record = AdditionRecordsModel::find(
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
    | Update Record
    |--------------------------------------------------------------------------
    |
    | مهم:
    | num_of_pages لا يتم أخذه من Flutter.
    |
    | إذا تغيرت بيانات القرآن:
    | from_surah
    | from_ayah
    | to_surah
    | to_ayah
    |
    | يتم إعادة حساب num_of_pages بواسطة QuranPageService.
    |
    */

    public function update_record(
        UpdateAdditionRecordRequest $request,
        QuranPageService $quranPageService
    ) {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | جلب السجل
            |--------------------------------------------------------------------------
            */

            $record = AdditionRecordsModel::find(
                $request->id
            );


            if (!$record) {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'السجل غير موجود'

                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | جلب الطالب
            |--------------------------------------------------------------------------
            */

            $student = StudentModel::find(
                $request->student_id
            );


            if (!$student) {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'لايوجد طالب'

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
            | منع تعديل num_of_pages يدوياً
            |--------------------------------------------------------------------------
            */

            if ($request->has('num_of_pages')) {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'num_of_pages يتم حسابه تلقائياً ولا يمكن تعديله يدوياً'

                ], 422);
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
                'student_id',
                'from_surah',
                'from_ayah',
                'to_surah',
                'to_ayah',
                'repeated_times',
                'memorization_state',
                'addition_date',
                'general_revision',
                'daily_revision',
            ];


            foreach ($commonFields as $field) {

                if ($request->has($field)) {

                    $updateData[$field] =
                        $request->input($field);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Check Quran Fields Changed
            |--------------------------------------------------------------------------
            */

            $quranFieldChanged =
                $request->has('from_surah') ||
                $request->has('from_ayah') ||
                $request->has('to_surah') ||
                $request->has('to_ayah');


            /*
            |--------------------------------------------------------------------------
            | Recalculate Number Of Pages
            |--------------------------------------------------------------------------
            */

            if ($quranFieldChanged) {

                /*
                |--------------------------------------------------------------------------
                | إذا أرسل Flutter قيمة جديدة نستخدمها،
                | وإذا لم يرسلها نستخدم القيمة القديمة.
                |--------------------------------------------------------------------------
                */

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
                | التأكد من وجود جميع بيانات القرآن
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
                            'يجب تحديد بداية ونهاية الحفظ كاملة لحساب عدد الصفحات'

                    ], 422);
                }


                /*
                |--------------------------------------------------------------------------
                | QuranPageService
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
                | حفظ القيمة المحسوبة
                |--------------------------------------------------------------------------
                */

                $updateData['num_of_pages'] =
                    $numOfPages;
            }


            /*
            |--------------------------------------------------------------------------
            | لا يوجد شيء لتحديثه
            |--------------------------------------------------------------------------
            */

            if (empty($updateData)) {

                DB::rollBack();

                return response()->json([

                    'message' =>
                        'لم يتم إرسال أي بيانات لتحديث السجل'

                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | تنفيذ التحديث
            |--------------------------------------------------------------------------
            */

            $record->update($updateData);


            /*
            |--------------------------------------------------------------------------
            | إضافة Note
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
                        now()

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
                    'تم تحديث السجل بنجاح',

                'record' =>
                    $record->fresh(),

                'note' =>
                    $note

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
    | Delete Record
    |--------------------------------------------------------------------------
    */

    public function delete(IdRequest $request)
    {
        DB::beginTransaction();

        try {

            $record = AdditionRecordsModel::find(
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
                    'حذف السجل بنجاح'

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
    | Get Monthly Pages
    |--------------------------------------------------------------------------
    */

    private function getMonthlyPages($studentId)
    {
        /*
        |--------------------------------------------------------------------------
        | الشهر والسنة الهجرية الحالية
        |--------------------------------------------------------------------------
        */

        $currentHijriMonth =
            Hijrian::hijri()->format('m');

        $currentHijriYear =
            Hijrian::hijri()->format('Y');


        $monthlyPages = 0;


        /*
        |--------------------------------------------------------------------------
        | جلب سجلات الطالب
        |--------------------------------------------------------------------------
        */

        $records = AdditionRecordsModel::where(
            'student_id',
            $studentId
        )->get();


        foreach ($records as $record) {

            /*
            |--------------------------------------------------------------------------
            | إذا لم يكن هناك تاريخ، نتجاهل السجل
            |--------------------------------------------------------------------------
            */

            if (!$record->addition_date) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | تحويل التاريخ إلى هجري
            |--------------------------------------------------------------------------
            */

            $hijriDate =
                Hijrian::hijri(
                    $record->addition_date
                );


            /*
            |--------------------------------------------------------------------------
            | مقارنة الشهر والسنة
            |--------------------------------------------------------------------------
            */

            if (
                $hijriDate->format('m') ==
                    $currentHijriMonth
                &&
                $hijriDate->format('Y') ==
                    $currentHijriYear
            ) {

                /*
                |--------------------------------------------------------------------------
                | num_of_pages محسوبة مسبقاً بواسطة QuranPageService
                |--------------------------------------------------------------------------
                */

                $monthlyPages +=
                    (int) $record->num_of_pages;
            }
        }


        return $monthlyPages;
    }
}