<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddSardStageRequest;
use App\Http\Requests\Api\UpdateSardStageRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RecordSardStagesModel;
use App\Models\NotificationsModel;
use App\Models\NotsModel;
use App\Models\StudentModel;

class RecordSardStagesController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | إنشاء سجل سرد
    |--------------------------------------------------------------------------
    */

   public function store(AddSardStageRequest $request)
{
    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | منع تكرار نفس السجل
        |--------------------------------------------------------------------------
        | لا يسمح بإضافة نفس نوع السرد لنفس الطالب في نفس اليوم.
        */
        $exists = RecordSardStagesModel::where('student_id', $request->student_id)
            ->where('sard_type', $request->sard_type)
            ->whereDate('insert_date', now()->toDateString())
            ->exists();

        if ($exists) {

            DB::rollBack();

            return response()->json([
                'message' => 'تم تسجيل هذا السرد لهذا الطالب اليوم مسبقًا'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | حساب عدد مرات الإعادة
        |--------------------------------------------------------------------------
        */
        $repeat = 0;

        if ($request->memorization_state == 'لم يحفظ') {

            $lastRecord = RecordSardStagesModel::where('student_id', $request->student_id)
                ->where('sard_type', $request->sard_type)
                ->latest('id')
                ->first();

            if ($lastRecord) {
                $repeat = $lastRecord->repeat_times + 1;
            } else {
                $repeat = 1;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | إنشاء سجل السرد
        |--------------------------------------------------------------------------
        */
        $record = RecordSardStagesModel::create([

            'student_id'          => $request->student_id,
            'sard_type'           => $request->sard_type,

            'memorization_state'  => $request->memorization_state,
            'hesitation_state'    => $request->hesitation_state,

            'total_melodies'      => $request->total_melodies ?? 0,
            'repeat_times'        => $repeat,
            'total_mistakes'      => $request->total_mistakes ?? 0,
            'sard_duration'       => $request->sard_duration,

            'insert_date'         => now(),

        ]);

        /*
        |--------------------------------------------------------------------------
        | إضافة الملاحظات
        |--------------------------------------------------------------------------
        */
        if (!empty($request->notes_text)) {

            NotsModel::create([

                'text_nots' => $request->notes_text,
                'teacher_id' => $request->teacher_id,
                'student_id' => $request->student_id,

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | فحص اكتمال السرد
        |--------------------------------------------------------------------------
        */
        $this->checkSardCompleted($request);

        /*
        |--------------------------------------------------------------------------
        | إرسال إشعار عند عدم الحفظ
        |--------------------------------------------------------------------------
        */
        if ($request->memorization_state == 'لم يحفظ') {

            if ($request->sard_type == 'سرد أول') {

                $this->createNotification(
                    $request->student_id,
                    'لم يكمل السرد الأول'
                );
            }

            if ($request->sard_type == 'سرد ثاني') {

                $this->createNotification(
                    $request->student_id,
                    'لم يكمل السرد الثاني'
                );
            }
        }

        DB::commit();

        return response()->json([

            'message' => 'تم إضافة سجل السرد بنجاح',
            'data' => $record

        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([

            'message' => $e->getMessage()

        ], 500);
    }
}








    /*
    |--------------------------------------------------------------------------
    | تعديل سجل السرد
    |--------------------------------------------------------------------------
    */

    public function update(UpdateSardStageRequest $request,$id)
    {


        DB::beginTransaction();



        try {



            $record = RecordSardStagesModel::findOrFail($id);

            $record->update([


                'sard_type'=>$request->sard_type,


                'memorization_state'=>$request->memorization_state,


                'total_melodies'=>$request->total_melodies,


                'total_mistakes'=>$request->total_mistakes,


                'note'=>$request->note,


            ]);



            if($request->memorization_state == 'لم يحفظ'){


                $record->update([

                    'repeat_times'=>$record->repeat_times + 1

                ]);



            }





            /*
             * فحص حالة السرد بعد التعديل
             */

            $this->checkSardCompleted($request);






            if($request->memorization_state == 'لم يحفظ'){



                if($request->sard_type == 'سرد أول'){


                    $this->createNotification(

                        $request->student_id,

                        'لم يكمل السرد الأول'

                    );


                }




                if($request->sard_type == 'سرد ثاني'){


                    $this->createNotification(

                        $request->student_id,

                        'لم يكمل السرد الثاني'

                    );


                }


            }






            DB::commit();




            return response()->json([


                'message'=>'تم تعديل سجل السرد بنجاح',


                'data'=>$record


            ]);





        }catch(\Exception $e){



            DB::rollBack();



            return response()->json([

                'message'=>$e->getMessage()

            ],500);



        }



    }









    /*
    |--------------------------------------------------------------------------
    | فحص حالة السرد وإنشاء إشعار الإكمال
    |--------------------------------------------------------------------------
    */

    private function checkSardCompleted(Request $request)
    {



        $record = RecordSardStagesModel::where(

            'student_id',

            $request->student_id

        )
        ->latest('id')
        ->first();





        if(!$record){

            return;

        }






        if($record->memorization_state == 'حفظ'){





            if($record->sard_type == 'سرد أول'){



                $this->createNotification(

                    $record->student_id,

                    'إكمال السرد الأول'

                );


            }






            if($record->sard_type == 'سرد ثاني'){



                $this->createNotification(

                    $record->student_id,

                    'إكمال السرد الثاني'

                );


            }



        }




    }









    /*
    |--------------------------------------------------------------------------
    | إنشاء إشعار
    |--------------------------------------------------------------------------
    */

    private function createNotification($student_id,$title)
    {


        $exists = NotificationsModel::where(

            'student_id',

            $student_id

        )
        ->where(

            'title',

            $title

        )
        ->exists();





        if($exists){

            return;

        }





       
    $student = StudentModel::find($student_id);

    if (!$student) {
        return;
    }

    NotificationsModel::create([
        'student_id' => $student_id,
        'halaqa_id'  => $student->halaqa_id,
        'title'      => $title,
        'notification_time' => now()->format('G:i:s'),
        'insert_date' => now()->toDateString(),
          
    ]);



    }



}