<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AdditionRecordsModel;
use App\Models\NotsModel;
use App\Models\StudentModel;
use MohamedSabil83\LaravelHijrian\Facades\Hijrian;
use App\Models\NotificationsModel;
use App\Models\User;

class AdditionRecordsController extends Controller
{
 
  public function add_addition_records(
    Request $request,
    User $user,
    StudentModel $student
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

            return response()->json([
                'message' => 'لم يتم إرسال أي سجل'
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | التحقق من الطالب
        |--------------------------------------------------------------------------
        */

        $studentId = $records[0]['student_id'] ?? null;

        if (!$studentId) {

            return response()->json([
                'message' => 'student_id مطلوب'
            ], 422);
        }


        $student = StudentModel::find($studentId);

        if (!$student) {

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

        if ($role != 'student') {

            return response()->json([
                'message' => 'ليس معرفاً كطالب'
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | فحص هل الطلب مراجعة فقط
        |--------------------------------------------------------------------------
        |
        | المراجعة فقط تكون عندما لا توجد بيانات الحفظ.
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
                | إشعار الربط العام
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

                'message' =>
                    'تم إضافة إشعارات المراجعة بنجاح',

                'count' =>
                    count($records),

            ], 201);
        }


        /*
        |--------------------------------------------------------------------------
        | إضافة سجلات الحفظ
        |--------------------------------------------------------------------------
        */

        $createdRecords = [];
        $createdNotes = [];


        foreach ($records as $data) {

            /*
            |--------------------------------------------------------------------------
            | التحقق من student_id
            |--------------------------------------------------------------------------
            */

        

            /*
            |--------------------------------------------------------------------------
            | إنشاء سجل الإضافة
            |--------------------------------------------------------------------------
            */

            $record = AdditionRecordsModel::create([

                'student_id' =>
                    $data['student_id'],

                'num_of_pages' =>
                    $data['num_of_pages'] ?? null,

                'from_surah' =>
                    $data['from_surah'] ?? null,

                'from_ayah' =>
                    $data['from_ayah'] ?? null,

                'to_surah' =>
                    $data['to_surah'] ?? null,

                'to_ayah' =>
                    $data['to_ayah'] ?? null,

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


            $createdRecords[] = $record;


            /*
            |--------------------------------------------------------------------------
            | Request خاص بهذا Record
            |--------------------------------------------------------------------------
            |
            | لأن createNotification() الحالية تستقبل Request
            |
            */

            $notificationRequest = new Request([
                'student_id' =>
                    $data['student_id'],
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
        |
        | مرة واحدة بعد إضافة جميع السجلات.
        |
        */

        $this->IsCompleated($student->id);


        /*
        |--------------------------------------------------------------------------
        | إضافة الملاحظة
        |--------------------------------------------------------------------------
        |
        | الملاحظة عادة مرتبطة بالطلب وليس بكل Record.
        |
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


public function IsCompleated( int $student_id)
{
    $firstRecord = AdditionRecordsModel::where('student_id',$student_id)
        ->oldest('id')
        ->first();


    $lastRecord = AdditionRecordsModel::where('student_id',$student_id)
        ->latest('id')
        ->first();


    if(!$firstRecord || !$lastRecord){
        return;
    }


    $completed =
        (
        $firstRecord->from_surah == 'الناس'
        &&
        $lastRecord->to_surah == 'البقرة'
        )
        ||
        (
        $firstRecord->from_surah == 'البقرة'
        &&
        $lastRecord->to_surah == 'الناس'
        );


    if($completed){

        $exists = NotificationsModel::where('student_id',$student_id)
        ->where('title','إشعار إكمال مرحلة الإضافة')
        ->exists();


        if(!$exists){
   $halaqa_id = StudentModel::where('id',$student_id)
    ->value('halaqa_id');

NotificationsModel::create([
    'student_id'=>$student_id,
    'halaqa_id'=>$halaqa_id,
    'title'=>'إشعار إكمال مرحلة الإضافة',
    'notification_time'=>now()->format('G:i:s'),
    'insert_date'=>now()->toDateString()
]);

        }
    }
}



private function createNotification($request,$title)
{

    $student = StudentModel::find($request->student_id);


    if(!$student){

        throw new \Exception(
            'الطالب غير موجود'
        );

    }

        NotificationsModel::create([

            'student_id'=>$student->id,

            'halaqa_id'=>$student->halaqa_id,

            'title'=>$title,

            'notification_time'=>now()->format('G:i:s'),

            'insert_date'=>now()->toDateString()

        ]);


}






    public function get_all_record()
    {


        $records = AdditionRecordsModel::all();


        return response()->json([

            'message'=>'تم استرجاع السجلات بنجاح',

            'records'=>$records

        ],200);


    }






    public function get_special_record(Request $request)
    {


        $record = AdditionRecordsModel::find($request->id);



        if(!$record){

            return response()->json([

         'message'=>'لم يتم العثور على السجل'

            ],404);

        }



        return response()->json([

            'message'=>'تم استرجاع السجل بنجاح  ',

            'record'=>$record

        ],200);


    }







    public function update_record(Request $request)
    {

        DB::beginTransaction();


        try{
   

            $record = AdditionRecordsModel::find($request->id);



            if(!$record){

                return response()->json([

                    'message'=>'السجل غير موجود '

                ],404);

            }

$student = StudentModel::find($request->student_id);
            
            if (!$student) {
                return response()->json(['message' => 'لايوجد طالب'], 404);
            }
            $role = User::where('id', $student->user_id)->value('role');
            if($role != 'student'){
                return response()->json([

                    'massage' => 'ليس  معرفاً'
                ]);
                }

            $record->update([


                'student_id'=>$request->student_id,

                'num_of_pages'=>$request->num_of_pages,

                'from_surah'=>$request->from_surah,

                'from_ayah'=>$request->from_ayah,

                'to_surah'=>$request->to_surah,

                'to_ayah'=>$request->to_ayah,

                'repeated_times'=>$request->repeated_times,

                'memorization_state'=>$request->memorization_state,

                'addition_date'=>$request->addition_date,

                'general_revision'=>$request->general_revision,

                'daily_revision'=>$request->daily_revision,


            ]);




            $note=null;



            if(!empty($request->notes_text)){


                $note=NotsModel::create([


                    'text_nots'=>$request->notes_text,

                    'teacher_id'=>$request->teacher_id,

                    'student_id'=>$request->student_id,

                    'insert_date'=>now()


                ]);

            }




            DB::commit();



            return response()->json([

                'message'=>'تم تحديث السجل بنجاح ',

                'record'=>$record,

                'note'=>$note

            ],200);



        }catch(\Exception $e){


            DB::rollBack();


            return response()->json([

                'message'=>$e->getMessage()

            ],500);

        }

    }







    public function delete(Request $request)
    {

        DB::beginTransaction();


        try{


            $record = AdditionRecordsModel::find($request->id);



            if(!$record){

                return response()->json([

                    'message'=>'السجل غير موجود'

                ],404);

            }



            $record->delete();



            DB::commit();



            return response()->json([

                'message'=>'حذف السجل بنجاح '

            ],200);



        }catch(\Exception $e){


            DB::rollBack();



            return response()->json([

                'message'=>$e->getMessage()

            ],500);


        }

    }

private function getMonthlyPages($studentId)
{
    // الشهر والسنة الهجرية الحالية
    $currentHijriMonth = Hijrian::hijri()->format('m');
    $currentHijriYear  = Hijrian::hijri()->format('Y');

    $monthlyPages = 0;

    // جلب سجلات الإضافة الخاصة بالطالب
    $records = AdditionRecordsModel::where('student_id', $studentId)->get();

    foreach ($records as $record) {

        // تحويل تاريخ الإضافة من ميلادي إلى هجري
        $hijriDate = Hijrian::hijri($record->addition_date);

        // مقارنة الشهر والسنة الهجرية
        if (
            $hijriDate->format('m') == $currentHijriMonth &&
            $hijriDate->format('Y') == $currentHijriYear
        ) {
            $monthlyPages += $record->num_of_pages;
        }
    }

    return $monthlyPages;
}


}