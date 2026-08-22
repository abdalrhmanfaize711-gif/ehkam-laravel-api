<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SerdSchedulesModel;
use App\Models\NotsModel;

class SerdSchedulesController extends Controller
{

    public function add_serd_schedule(Request $request)
    {
        DB::beginTransaction();

        try {

            $serdSchedule = SerdSchedulesModel::create([
                
                'student_id' => $request->student_id,
                'total_assigned_juz' => $request->total_assigned_juz,
                'num_of_days' => $request->num_of_days,
                'insert_date' => now(),

            ]);


            DB::commit();


            return response()->json([

                'message'=>'تمت إضافة جدول Serd بنجاح',
                'serd_schedule'=>$serdSchedule

            ],201);



        } catch(\Exception $e){

            DB::rollBack();


            return response()->json([

                'message'=>$e->getMessage()

            ],500);

        }

    }





    public function get_all_serd_schedule()
    {

        $serdSchedules = SerdSchedulesModel::all();


        return response()->json([

            'message'=>'تم استرجاع جداول Serd بنجاح',
            'serd_schedule'=>$serdSchedules

        ],200);

    }






    public function get_special_serd_schedule(Request $request)
    {

        $serdSchedule = SerdSchedulesModel::find($request->id);


        if(!$serdSchedule){

            return response()->json([

                'message'=>'لم يتم العثور على جدول Serd'

            ],404);

        }


        return response()->json([

            'message'=>'تم استرجاع جدول Serd بنجاح',
            'serd_schedule'=>$serdSchedule

        ],200);

    }







    public function update_serd_schedule(Request $request)
    {

        DB::beginTransaction();


        try {


            $serdSchedule = SerdSchedulesModel::find($request->id);



            if(!$serdSchedule){

                return response()->json([

                    'message'=>'لم يتم العثور على جدول Serd'

                ],404);

            }



            $serdSchedule->update([

                'student_id' => $request->student_id,
                'total_assigned_juz' => $request->total_assigned_juz,
                'num_of_days' => $request->num_of_days,

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

                'message'=>'تم تحديث جدول Serd بنجاح',
                'serd_schedule'=>$serdSchedule,
                'notes'=>$note

            ],200);



        }catch(\Exception $e){


            DB::rollBack();


            return response()->json([

                'message'=>$e->getMessage()

            ],500);

        }

    }








    public function delete_serd_schedule(Request $request)
    {

        DB::beginTransaction();


        try {


            $serdSchedule = SerdSchedulesModel::find($request->id);
            $serdSchedule = SerdSchedulesModel::find($request->id);


            if(!$serdSchedule){

                return response()->json([

                    'message'=>'لم يتم العثور على جدول Serd'

                ],404);

            }



            $serdSchedule->delete();



            DB::commit();



            return response()->json([

                'message'=>'تم حذف جدول Serd بنجاح'

            ],200);



        }catch(\Exception $e){


            DB::rollBack();


            return response()->json([

                'message'=>$e->getMessage()

            ],500);

        }

    }

}