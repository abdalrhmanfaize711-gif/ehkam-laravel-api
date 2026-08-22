<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RecoredExamsModel;
use App\Models\NotificationsModel;
use App\Models\StudentModel;
use App\Models\NotsModel;

class RecoredExamsController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | إنشاء إشعار تعثر الاختبار
    |--------------------------------------------------------------------------
    */
private function createExamNotification($student_id, $title)
{
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
        'student_id' => $student_id,
        'halaqa_id' => $student->halaqa_id,
        'title' => $title,
        'notification_time' => now()->format('H:i:s'),
        'insert_date' => now()->toDateString(),
    ]);
}

    /*
    |--------------------------------------------------------------------------
    | فحص نتيجة الاختبار
    |--------------------------------------------------------------------------
    */
private function checkExamResult($student_id, $percentage, Request $request)
{
    if ($percentage < 50) {

        if ($request->exam_type == 'مباغتة') {

            $this->createExamNotification(
                $student_id,
                'تعثر في اختبار مباغتة'
            );

        } else {

            $this->createExamNotification(
                $student_id,
                'تعثر في اختبار رسمي'
            );

        }
    }
}



    /*
    |--------------------------------------------------------------------------
    | إضافة نتيجة اختبار
    |--------------------------------------------------------------------------
    */

    public function add_record_exam(Request $request)
    {

        DB::beginTransaction();


        try {


            $recordExam = RecoredExamsModel::create([


                'student_id' => $request->student_id,

                'teacher_id' => $request->teacher_id,

                'num_of_questions' => $request->num_of_questions,

                'total_mistakes' => $request->total_mistakes,

                'total_melodies' => $request->total_melodies,

                'total_hesitiations' => $request->total_hesitiations,

                'final_percentage' => $request->final_percentage,

                'exam_type' => $request->exam_type,

                'insert_date' => $request->insert_date,


            ]);





            /*
             * فحص التعثر
             */

      $this->checkExamResult(
    $request->student_id,
    $request->final_percentage,
    $request
);




            $note = null;



            if(!empty($request->notes_text)){


                $note = NotsModel::create([


                    'text_nots' => $request->notes_text,

                    'teacher_id' => $request->teacher_id,

                    'student_id' => $request->student_id,

                    'insert_date' => now(),


                ]);


            }





            DB::commit();



            return response()->json([


                'message' => 'تم إضافة سجل الامتحان بنجاح',

                'record_exam' => $recordExam,

                'notes' => $note


            ],201);





        }catch(\Exception $e){


            DB::rollBack();



            return response()->json([

                'message'=>$e->getMessage()

            ],500);


        }


    }







    /*
    |--------------------------------------------------------------------------
    | جلب جميع النتائج
    |--------------------------------------------------------------------------
    */

    public function get_all_record_exam()
    {


        $records = RecoredExamsModel::all();



        return response()->json([


            'message'=>'تم استرجاع السجلات بنجاح',

            'record_exam'=>$records


        ],200);


    }







    /*
    |--------------------------------------------------------------------------
    | جلب نتيجة محددة
    |--------------------------------------------------------------------------
    */

    public function get_special_record_exam(Request $request)
    {


        $recordExam = RecoredExamsModel::find($request->id);



        if(!$recordExam){


            return response()->json([

                'message'=>'لم يتم العثور على سجل الفحص'

            ],404);


        }




        return response()->json([


            'message'=>'تم استرجاع سجل الفحص بنجاح',

            'record_exam'=>$recordExam


        ],200);


    }









    /*
    |--------------------------------------------------------------------------
    | تعديل نتيجة اختبار
    |--------------------------------------------------------------------------
    */

    public function update_record_exam(Request $request)
    {

        DB::beginTransaction();


        try {


            $recordExam = RecoredExamsModel::find($request->id);



            if(!$recordExam){


                return response()->json([

                    'message'=>'لم يتم العثور على سجل الفحص'

                ],404);


            }




            $recordExam->update([


                'student_id' => $request->student_id,

                'teacher_id' => $request->teacher_id,

                'num_of_questions' => $request->num_of_questions,

                'total_mistakes' => $request->total_mistakes,

                'total_melodies' => $request->total_melodies,

                'total_hesitiations' => $request->total_hesitiations,

                'final_percentage' => $request->final_percentage,

                'exam_type' => $request->exam_type,

                'insert_date' => $request->insert_date,


            ]);







            /*
             * فحص التعثر بعد التعديل
                         */
            $this->checkExamResult(
                $request->student_id,
                $request->final_percentage,
                $request
            );






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



                'message'=>'تم تحديث سجل الفحص بنجاح',


                'record_exam'=>$recordExam,


                'notes'=>$note



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
حصاد     |--------------------------------------------------------------------------
    */

public function surprise_exam_statistics()
{
    try {

        $totalExams = RecoredExamsModel::where(
            'exam_type',
            'اختبار مباغت'
        )->count();


        $failedStudents = RecoredExamsModel::where(
            'exam_type',
            'اختبار مباغت'
        )
        ->where('final_percentage', '<', 50)
        ->count();


        $passedStudents = RecoredExamsModel::where(
            'exam_type',
            'اختبار مباغت'
        )
        ->where('final_percentage', '>=', 50)
        ->count();


        return response()->json([
            'total_surprise_exams' => $totalExams,
            'failed_students'      => $failedStudents,
            'passed_students'      => $passedStudents,
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'message' => $e->getMessage()
        ], 500);

    }
}


    /*
    |--------------------------------------------------------------------------
    | حذف نتيجة اختبار
    |--------------------------------------------------------------------------
    */

     public function delete_record_exam(Request $request)
    {


        DB::beginTransaction();


        try {


            $recordExam = RecoredExamsModel::find($request->id);



            if(!$recordExam){


                return response()->json([

                    'message'=>'لم يتم العثور على سجل الفحص'

                ],404);


            }





            $recordExam->delete();




            DB::commit();




            return response()->json([


                'message'=>'تم حذف سجل الفحص بنجاح'


            ],200);





        }catch(\Exception $e){



            DB::rollBack();



            return response()->json([

                'message'=>$e->getMessage()

            ],500);



        }


    }





}