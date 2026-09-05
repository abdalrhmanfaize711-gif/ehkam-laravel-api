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
            |
            | إذا كان الطلب يحتوي فقط على:
            |
            | student_id
            | general_revision
            | daily_revision
            |
            | ولا يحتوي على بيانات الحفظ.
            |
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

                    /*
                    |--------------------------------------------------------------------------
                    | إشعار الربط العام
                    |--------------------------------------------------------------------------
                    */

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
                | التحقق من بيانات القرآن
                |--------------------------------------------------------------------------
                |
                | لا نعتمد على num_of_pages المرسل من Flutter.
                |
                | يجب إرسال:
                |
                | from_surah
                | from_ayah
                | to_surah
                | to_ayah
                |
                */

                $fromSurah = $data['from_surah'] ?? null;
                $fromAyah  = $data['from_ayah'] ?? null;
                $toSurah   = $data['to_surah'] ?? null;
                $toAyah    = $data['to_ayah'] ?? null;


                /*
                |--------------------------------------------------------------------------
                | حساب عدد الصفحات
                |--------------------------------------------------------------------------
                */

                $numOfPages = null;

                if (
                    $fromSurah !== null &&
                    $fromAyah !== null &&
                    $toSurah !== null &&
                    $toAyah !== null
                ) {

                    $numOfPages = $quranPageService->calculatePages(
                        $fromSurah,
                        $fromAyah,
                        $toSurah,
                        $toAyah
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | إنشاء سجل الإضافة
                |--------------------------------------------------------------------------
                */

                $record = AdditionRecordsModel::create([

                    'student_id' =>
                        $recordStudentId,

                    /*
                    |------------------------------------------------------------------
                    | مهم:
                    | هذا الرقم محسوب من QuranPageService
                    | وليس من Flutter.
                    |------------------------------------------------------------------
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
    | عند التعديل أيضاً لا نأخذ num_of_pages من Flutter.
    |
    | نقوم بإعادة حسابه من:
    |
    | from_surah
    | from_ayah
    | to_surah
    | to_ayah
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
            | بيانات القرآن الجديدة
            |--------------------------------------------------------------------------
            */

            $fromSurah = $request->from_surah;
            $fromAyah  = $request->from_ayah;
            $toSurah   = $request->to_surah;
            $toAyah    = $request->to_ayah;


            /*
            |--------------------------------------------------------------------------
            | إعادة حساب عدد الصفحات
            |--------------------------------------------------------------------------
            */

            $numOfPages = null;

            if (
                $fromSurah !== null &&
                $fromAyah !== null &&
                $toSurah !== null &&
                $toAyah !== null
            ) {

                $numOfPages = $quranPageService->calculatePages(
                    $fromSurah,
                    $fromAyah,
                    $toSurah,
                    $toAyah
                );
            }


            /*
            |--------------------------------------------------------------------------
            | تحديث السجل
            |--------------------------------------------------------------------------
            */

            $record->update([

                'student_id' =>
                    $request->student_id,

                /*
                |------------------------------------------------------------------
                | محسوبة من QuranPageService
                |------------------------------------------------------------------
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
                    $request->repeated_times,

                'memorization_state' =>
                    $request->memorization_state,

                'addition_date' =>
                    $request->addition_date,

                'general_revision' =>
                    $request->general_revision,

                'daily_revision' =>
                    $request->daily_revision,

            ]);


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
                |--------------------------------------------------------------
                | num_of_pages أصبح محسوباً مسبقاً بواسطة QuranPageService
                |--------------------------------------------------------------
                */

                $monthlyPages +=
                    (int) $record->num_of_pages;
            }
        }


        return $monthlyPages;
    }
}
