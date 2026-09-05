<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddScheduledSardDayRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\IdStudentRequest;
use App\Http\Requests\Api\StoreRecordSardDayRequest;
use App\Http\Requests\Api\UpdateRecordSardDayRequest;
use App\Http\Requests\Api\UpdateScheduledSardDayRequest;
use App\Http\Requests\Api\DeleteRecordSardDayRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RecordSardDaysModel;
use App\Models\StudentModel;
use App\Models\NotificationsModel;
use App\Models\ScheduledSardDaysModel;
use App\Models\SerdSchedulesModel;
use App\Models\SardRecordsModel;

class RecordSardDaysController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | إنشاء إشعار عدم إكمال مقرر السرد
    |--------------------------------------------------------------------------
    */

    private function createSardDayNotification($student_id, $remaining_sheets)
    {
        $title = "لم يكمل مقرر السرد - المتبقي {$remaining_sheets} أوراق";

        $exists = NotificationsModel::where('student_id', $student_id)
            ->where('title', $title)
            ->exists();

        if ($exists) {
            return;
        }

        $student = StudentModel::find($student_id);

        if (!$student) {
            return;
        }

        NotificationsModel::create([
            'student_id'       => $student_id,
            'halaqa_id'        => $student->halaqa_id,
            'title'            => $title,
            'notification_time' => now()->format('G:i:s'),
            'insert_date'      => now()->toDateString(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | فحص إكمال مقرر السرد
    |--------------------------------------------------------------------------
    */

    private function checkSardDayCompleted($student_id, $remaining_sheets)
    {
        if ($remaining_sheets != 0) {

            $this->createSardDayNotification(
                $student_id,
                $remaining_sheets
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | حفظ نسخة من Schedule في Sard Record
    |--------------------------------------------------------------------------
    |
    | هذه العملية تتم مرة واحدة فقط لكل خطة.
    |
    */

    private function saveSardScheduleToRecord($schedule)
    {
        /*
        |--------------------------------------------------------------------------
        | التحقق هل تم حفظ هذه الخطة سابقاً
        |--------------------------------------------------------------------------
        |
        | نعتمد على:
        | student_id
        | teacher_id
        | insert_date
        |
        | لأن insert_date هو تاريخ إنشاء الخطة الأصلية.
        |
        */

        $exists = SardRecordsModel::where('student_id', $schedule->student_id)
            ->where('teacher_id', $schedule->teacher_id)
            ->whereDate('insert_date', $schedule->insert_date)
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | إذا كانت النسخة موجودة لا ننشئ نسخة أخرى
        |--------------------------------------------------------------------------
        */

        if ($exists) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | إنشاء نسخة كاملة من الخطة
        |--------------------------------------------------------------------------
        */

        return SardRecordsModel::create([
            'student_id'       => $schedule->student_id,
            'teacher_id'       => $schedule->teacher_id,
            'total_assigned_juz' => $schedule->total_assigned_juz,
            'num_of_days'      => $schedule->num_of_days,
            'insert_date'      => $schedule->insert_date,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Add Record Sard Day
    |--------------------------------------------------------------------------
    */

    public function add_record_sard_day(StoreRecordSardDayRequest $request)
    {
       

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. التحقق من عدم وجود نفس يوم السرد للطالب
            |--------------------------------------------------------------------------
            */

            $exists = RecordSardDaysModel::where(
                'student_id',
                $request->student_id
            )
            ->where(
                'sard_day',
                $request->sard_day
            )
            ->exists();

            if ($exists) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'هذا يوم السرد موجود مسبقاً لهذا الطالب.',
                ], 409);
            }


            /*
            |--------------------------------------------------------------------------
            | 2. جلب Schedule الخاص بالطالب
            |--------------------------------------------------------------------------
            */

            $serdSchedule = SerdSchedulesModel::where(
                'student_id',
                $request->student_id
            )
            ->lockForUpdate()
            ->first();


            /*
            |--------------------------------------------------------------------------
            | 3. إذا كانت هناك خطة سرد
            |--------------------------------------------------------------------------
            |
            | أول مرة فقط:
            | نأخذ نسخة كاملة من Schedule
            | ونضعها في Sard Record.
            |
            */

            $sardRecord = null;

            if ($serdSchedule) {

                $sardRecord = $this->saveSardScheduleToRecord(
                    $serdSchedule
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 4. إضافة يوم السرد
            |--------------------------------------------------------------------------
            */
    $sard_record = SardRecordsModel::where('student_id', $request->student_id)
    ->latest('id')
    ->first();

if (!$sard_record) {
    return response()->json([
        'message' => 'لا يوجد سجل سرد لهذا الطالب'
    ], 404);
}

$record = RecordSardDaysModel::create([
    'student_id' => $request->student_id,
    'num_of_session' => $request->num_of_session,
    'sard_record_id' => $sard_record->id,
    'sard_day' => $request->sard_day,
    'num_of_remaining_sheets' => $request->num_of_remaining_sheets,
]);

            /*
            |--------------------------------------------------------------------------
            | 5. إنقاص أيام Schedule
            |--------------------------------------------------------------------------
            */

            if ($serdSchedule) {

                /*
                | إنقاص يوم واحد
                */

                $serdSchedule->num_of_days =
                    max(
                        0,
                        $serdSchedule->num_of_days - 1
                    );

                /*
                |--------------------------------------------------------------------------
                | إذا أصبحت الأيام صفر
                | نحذف Schedule
                |--------------------------------------------------------------------------
                */

                if ($serdSchedule->num_of_days == 0) {

                    $serdSchedule->delete();

                } else {

                    $serdSchedule->save();
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 6. فحص المتبقي من مقرر السرد
            |--------------------------------------------------------------------------
            */

            $this->checkSardDayCompleted(
                $request->student_id,
                $request->num_of_remaining_sheets
            );


            /*
            |--------------------------------------------------------------------------
            | 7. Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | 8. Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' =>
                    'تم إضافة يوم السرد بنجاح.',

                'record' =>
                    $record,

                /*
                | إذا كانت هذه أول مرة يتم فيها إنشاء Sard Record
                */

                'sard_record' =>
                    $sardRecord,

                /*
                | الخطة الحالية بعد إنقاص اليوم
                |
                | إذا أصبحت 0 وتم حذفها ستكون null
                */

                'schedule_remaining_days' =>
                    $serdSchedule
                        ? $serdSchedule->num_of_days
                        : 0,
            ], 201);


        } catch (\Illuminate\Database\QueryException $e) {

            DB::rollBack();

            /*
            |--------------------------------------------------------------------------
            | Duplicate Unique Constraint
            |--------------------------------------------------------------------------
            */

            if (($e->errorInfo[1] ?? null) == 1062) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'هذا يوم السرد موجود مسبقاً لهذا الطالب.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get All Records
    |--------------------------------------------------------------------------
    */

    public function get_all_record_sard_days()
    {
        $records = RecordSardDaysModel::all();

        return response()->json([
            'message' => 'تم استرجاع السجلات بنجاح',
            'records' => $records
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | Get One Record
    |--------------------------------------------------------------------------
    */

    public function get_special_record_sard_day(IdStudentRequest $request)
    {
        $records = RecordSardDaysModel::where(
            'student_id',
            $request->student_id
        )
        ->orderBy('sard_day', 'desc')
        ->get();

        if ($records->isEmpty()) {

            return response()->json([
                'message' => 'لم يتم العثور على السجلات'
            ], 404);
        }

        return response()->json([
            'message' => 'تم استرجاع السجلات بنجاح',
            'records' => $records
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Record
    |--------------------------------------------------------------------------
    */

    public function update_record_sard_day(UpdateRecordSardDayRequest $request)
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | جلب سجل يوم السرد
            |--------------------------------------------------------------------------
            */

            $record = RecordSardDaysModel::find($request->id);

            if (!$record) {

                DB::rollBack();

                return response()->json([
                    'message' => 'لم يتم العثور على السجل'
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | حفظ بيانات يوم السرد القديم
            |--------------------------------------------------------------------------
            */

            $oldStudentId = $record->student_id;
            $oldSardDay   = $record->sard_day;


            /*
            |--------------------------------------------------------------------------
            | تحديث سجل يوم السرد
            |--------------------------------------------------------------------------
            */

            $record->update([
                'student_id' =>
                    $request->student_id,

                'num_of_session' =>
                    $request->num_of_session,

                'sard_day' =>
                    $request->sard_day,

                'num_of_remaining_sheets' =>
                    $request->num_of_remaining_sheets,
            ]);


            /*
            |--------------------------------------------------------------------------
            | حذف يوم السرد القديم من الجدول المجدول
            |--------------------------------------------------------------------------
            */

            ScheduledSardDaysModel::where(
                'student_id',
                $oldStudentId
            )
            ->where(
                'sard_day',
                $oldSardDay
            )
            ->delete();


            /*
            |--------------------------------------------------------------------------
            | ملاحظة مهمة:
            |
            | لا ننشئ Sard Record جديد هنا.
            |
            | لأن Sard Record يمثل نسخة الخطة الأصلية،
            | وليس نسخة لكل تعديل أو لكل يوم.
            |--------------------------------------------------------------------------
            */


            /*
            |--------------------------------------------------------------------------
            | فحص المتبقي من مقرر السرد
            |--------------------------------------------------------------------------
            */

            $this->checkSardDayCompleted(
                $request->student_id,
                $request->num_of_remaining_sheets
            );


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();


            return response()->json([
                'message' =>
                    'تم تحديث يوم السرد بنجاح.',

                'record' =>
                    $record,
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

    public function delete_record_sard_day(DeleteRecordSardDayRequest $request)
    {
        DB::beginTransaction();

        try {

            $record = RecordSardDaysModel::find($request->id);

            if (!$record) {

                DB::rollBack();

                return response()->json([
                    'message' =>
                        'لم يتم العثور على السجل'
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | حذف سجل يوم السرد فقط
            |--------------------------------------------------------------------------
            |
            | لا نحذف Sard Record
            | ولا نعيد Schedule.
            |
            */

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