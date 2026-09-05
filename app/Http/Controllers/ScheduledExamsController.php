<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddScheduledExamRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateScheduledExamRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ScheduledExamsModel;
use App\Models\NotsModel;

class ScheduledExamsController extends Controller
{
    

    public function add_scheduled_exam(AddScheduledExamRequest $request)
    {
        DB::beginTransaction();

        try {

            $scheduledExam = ScheduledExamsModel::create([

                'student_id' => $request->student_id,
                'teacher_id' => $request->teacher_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'num_of_juz' => $request->num_of_juz,
                'from_surah' => $request->from_surah,
                'from_ayah' => $request->from_ayah,
                'to_surah' => $request->to_surah,
                'to_ayah' => $request->to_ayah,
                'num_of_questions' => $request->num_of_questions,

            ]);


            DB::commit();


            return response()->json([

                'message'=>'تم إضافة الامتحان المقرر بنجاح',
                'scheduled_exam'=>$scheduledExam

            ],201);



        } catch(\Exception $e){

            DB::rollBack();


            return response()->json([

                'message'=>$e->getMessage()

            ],500);

        }
    }





    public function get_all_scheduled_exams()
    {

        $scheduledExams = ScheduledExamsModel::all();


        return response()->json([

            'message'=>'تم استرداد الامتحانات المجدولة بنجاح',
            'scheduled_exams'=>$scheduledExams

        ],200);

    }






    public function get_special_scheduled_exams(IdRequest $request)
    {

        $scheduledExam = ScheduledExamsModel::find($request->id);


        if(!$scheduledExam){

            return response()->json([

                'message'=>'لم يتم العثور على الامتحان المقرر'

            ],404);

        }


        return response()->json([

            'message'=>'تم استرداد الامتحان المقرر بنجاح',
            'scheduled_exam'=>$scheduledExam

        ],200);

    }







    public function update_scheduled_exams(UpdateScheduledExamRequest $request)
    {

        DB::beginTransaction();


        try {


            $scheduledExam = ScheduledExamsModel::find($request->id);


            if(!$scheduledExam){

                return response()->json([

                    'message'=>'لم يتم العثور على الامتحان المقرر'

                ],404);

            }



            $scheduledExam->update([

                'student_id' => $request->student_id,
                'teacher_id' => $request->teacher_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'num_of_juz' => $request->num_of_juz,
                'from_surah' => $request->from_surah,
                'from_ayah' => $request->from_ayah,
                'to_surah' => $request->to_surah,
                'to_ayah' => $request->to_ayah,
                'num_of_questions' => $request->num_of_questions,

            ]);



            $note = null;


            if(!empty($request->notes_text)){


                $note = NotsModel::create([

                    'text_nots'=>$request->notes_text,
                    'teacher_id'=>$request->teacher_id,
                    'student_id'=>$request->student_id,
                    'insert_date'=>now(),

                ]);

            }



            DB::commit();



            return response()->json([

                'message'=>'تم تحديث موعد الامتحان بنجاح',
                'scheduled_exam'=>$scheduledExam,
                'notes'=>$note

            ],200);



        }catch(\Exception $e){


            DB::rollBack();


            return response()->json([

                'message'=>$e->getMessage()

            ],500);

        }

    }







    public function delete_scheduled_exams(IdRequest $request)
    {

        DB::beginTransaction();


        try {


            $scheduledExam = ScheduledExamsModel::find($request->id);



            if(!$scheduledExam){

                return response()->json([

                    'message'=>'لم يتم العثور على الامتحان المقرر'

                ],404);

            }



            $scheduledExam->delete();


            DB::commit();



            return response()->json([

                'message'=>'تم حذف الامتحان المقرر بنجاح'

            ],200);



        }catch(\Exception $e){


            DB::rollBack();


            return response()->json([

                'message'=>$e->getMessage()

            ],500);

        }

    }

}