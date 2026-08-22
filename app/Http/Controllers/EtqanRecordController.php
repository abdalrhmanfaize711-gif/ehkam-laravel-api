<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EtqanRecordModel;
use App\Models\NotsModel;
use App\Models\NotificationsModel;
use App\Models\StudentModel;
use App\Models\User;

class EtqanRecordController extends Controller
{

  public function add_etqan_records(Request $request)
{
    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | تحديد هل الطلب يحتوي على Record واحد أو عدة Records
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
        | جلب الطالب
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
                'message' => 'الطالب غير موجود'
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
        | حالة المراجعة فقط
        |--------------------------------------------------------------------------
        |
        | general_revision فقط بدون بيانات الإتقان
        |
        */

        $firstRecord = $records[0];

        $isRevisionOnly =
            isset($firstRecord['student_id']) &&
            array_key_exists('general_revision', $firstRecord) &&

            !isset($firstRecord['from_surah']) &&
            !isset($firstRecord['from_ayah']) &&
            !isset($firstRecord['to_surah']) &&
            !isset($firstRecord['to_ayah']) &&
            !isset($firstRecord['num_of_sheets']) &&
            !isset($firstRecord['memorization_state']);


        /*
        |--------------------------------------------------------------------------
        | المراجعة فقط
        |--------------------------------------------------------------------------
        */

        if ($isRevisionOnly) {

            foreach ($records as $data) {

                if (
                    ($data['general_revision'] ?? false) == false ||
                    ($data['general_revision'] ?? false) == 0
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
            | التحقق من الطالب
            |--------------------------------------------------------------------------
            */


            /*
            |--------------------------------------------------------------------------
            | إنشاء سجل الإتقان
            |--------------------------------------------------------------------------
            */

            $record = EtqanRecordModel::create([

                'student_id' =>
                    $data['student_id'],

                'from_surah' =>
                    $data['from_surah'] ?? null,

                'from_ayah' =>
                    $data['from_ayah'] ?? null,

                'to_surah' =>
                    $data['to_surah'] ?? null,

                'to_ayah' =>
                    $data['to_ayah'] ?? null,

                'num_of_sheets' =>
                    $data['num_of_sheets'] ?? null,

                'memorization_state' =>
                    $data['memorization_state'] ?? null,

                'general_revision' =>
                    $data['general_revision'] ?? false,

                'addition_date' =>
                    $data['addition_date'] ?? null,

            ]);


            $createdRecords[] = $record;


            /*
            |--------------------------------------------------------------------------
            | Request خاص بهذا Record
            |--------------------------------------------------------------------------
            */

            $notificationRequest = new Request([
                'student_id' =>
                    $data['student_id'],
            ]);


            /*
            |--------------------------------------------------------------------------
            | إشعار عدم الحفظ
            |--------------------------------------------------------------------------
            */

            if (
                ($data['memorization_state'] ?? null) == 'لم يحفظ'
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
                ($data['general_revision'] ?? false) == false ||
                ($data['general_revision'] ?? false) == 0
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
        |
        | يتم الفحص مرة واحدة بعد إضافة جميع السجلات.
        |
        */

        $this->checkEtqanCompleted(
            new Request([
                'student_id' => $student->id
            ])
        );


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


        // أول سجل للطالب

        $firstRecord = EtqanRecordModel::where(
            'student_id',
            $request->student_id
        )
        ->oldest('id')
        ->first();



        // آخر سجل للطالب

        $lastRecord = EtqanRecordModel::where(
            'student_id',
            $request->student_id
        )
        ->latest('id')
        ->first();



        if(!$firstRecord || !$lastRecord){

            return;

        }


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



        if(!$completed){

            return;

        }




        // جلب الطالب

        $student = StudentModel::find(
            $request->student_id
        );


        if(!$student){

            return;

        }



        switch($student->stage){


            case 'اتقان اول':


                $title = 'إكمال إتقان أول';


                $student->update([

                    'stage'=>'اتقان ثاني'

                ]);


            break;



            case 'اتقان ثاني':


                $title = 'إكمال إتقان ثاني';


                $student->update([

                    'stage'=>'اتقان ثالث'

                ]);


            break;




            case 'اتقان ثالث':


                $title = 'إكمال إتقان ثالث';


            break;



            default:

                return;

        }




        // إنشاء إشعار الإكمال

        $this->createNotification(
            $request,
            $title
        );


    }





    /*
    |--------------------------------------------------------------------------
    | إنشاء الإشعار
    |--------------------------------------------------------------------------
    */


    private function createNotification($request,$title)
    {


        // منع تكرار إشعار نفس المرحلة

        $exists = NotificationsModel::where(
            'student_id',
            $request->student_id
        )
        ->where(
            'title',
            $title
        )
        ->exists();



        if($exists){

            return;

        }

 $halaqa = StudentModel::where('student_id', $request->student_id)->get();


        NotificationsModel::create([


            'student_id'=>$request->student_id,


            'halaqa_id'=>$halaqa->halaqa_id,


            'title'=>$title,


            'notification_time'=>now()->format('H:i:s'),


            'insert_date'=>now()->toDateString(),


        ]);


    }
        /*
    |--------------------------------------------------------------------------
    | جلب جميع سجلات الإتقان
    |--------------------------------------------------------------------------
    */

    public function get_all_record()
    {

        $records = EtqanRecordModel::all();


        return response()->json([

            'message'=>'تم استرجاع السجلات بنجاح',

            'records'=>$records

        ],200);


    }





    /*
    |--------------------------------------------------------------------------
    | جلب سجل واحد
    |--------------------------------------------------------------------------
    */

    public function get_special_record(Request $request)
    {

        $record = EtqanRecordModel::find($request->id);



        if(!$record){

            return response()->json([

                'message'=>'لم يتم العثور على السجل'

            ],404);

        }



        return response()->json([

            'message'=>'تم استرجاع السجل بنجاح',

            'record'=>$record

        ],200);


    }






    /*
    |--------------------------------------------------------------------------
    | تحديث سجل الإتقان
    |--------------------------------------------------------------------------
    */

    public function update_record(Request $request)
    {

        DB::beginTransaction();


        try{


            $record = EtqanRecordModel::find($request->id);



            if(!$record){


                return response()->json([

                    'message'=>'لم يتم العثور على السجل'

                ],404);


            }

$student = StudentModel::find($request->student_id);
            
            if (!$student) {
                return response()->json(['message' => 'لم يتم العثور على الطالب'], 404);
            }
            $role = User::where('id', $student->user_id)->value('role');
            if($role != 'student'){
                return response()->json([

                    'massage' => 'ليس  معرفاً'
                ]);
            }



            $record->update([


                'student_id'=>$request->student_id,

                'from_surah'=>$request->from_surah,

                'from_ayah'=>$request->from_ayah,

                'to_surah'=>$request->to_surah,

                'to_ayah'=>$request->to_ayah,

                'num_of_sheets'=>$request->num_of_sheets,

                'memorization_state'=>$request->memorization_state,

                'general_revision'=>$request->general_revision,
                
                'addition_date'=>$request->addition_date,


            ]);





            // إضافة ملاحظة

            $note=null;



            if(!empty($request->notes_text)){


                $note=NotsModel::create([


                    'text_nots'=>$request->notes_text,


                    'teacher_id'=>$request->teacher_id,


                    'student_id'=>$request->student_id,


                    'insert_date'=>now(),


                ]);

            }





            DB::commit();



            return response()->json([


                'message'=>'تم تحديث  سجل الإتقان  بنجاح ',


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







    /*
    |--------------------------------------------------------------------------
    | حذف سجل الإتقان
    |--------------------------------------------------------------------------
    */

    public function delete(Request $request)
    {


        DB::beginTransaction();



        try{


            $record = EtqanRecordModel::find($request->id);



            if(!$record){


                return response()->json([

                    'message'=>'السجل غير موجود'

                ],404);


            }





            $record->delete();





            DB::commit();





            return response()->json([


                'message'=>'تم حذف السجل بنجاح'


            ],200);





        }catch(\Exception $e){


            DB::rollBack();

            return response()->json([

                'message'=>$e->getMessage()

            ],500);


        }


    }


}