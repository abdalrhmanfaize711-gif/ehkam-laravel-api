<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ScheduledSardDaysModel;

class ScheduledSardDaysController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Add Scheduled successfullyDay
    |--------------------------------------------------------------------------
    */

    public function add_scheduled_sard_day(Request $request)
    {
        DB::beginTransaction();

        try {

            $record = ScheduledSardDaysModel::create([

                'student_id'    => $request->student_id,
                'sard_day'      => $request->sard_day,
                'num_of_assigned_session'=> $request->num_of_assigned_session,
                'teacher_id'    => $request->teacher_id,
                'from_surah'    => $request->from_surah,
                'from_ayah'     => $request->from_ayah,
                'to_surah'      => $request->to_surah,
                'to_ayah'       => $request->to_ayah,
                'time_assigned' => $request->time_assigned,
                'sard_date'     => $request->sard_date,
                'insert_date'   => $request->insert_date,

            ]);

            DB::commit();

            return response()->json([

                'message' => 'تم إضافة يوم السرد المجدول بنجاح',
                'record'  => $record

            ],201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'message' => $e->getMessage()

            ],500);

        }

    }



    /*
    |--------------------------------------------------------------------------
    | Get All Records
    |--------------------------------------------------------------------------
    */

    public function get_all_scheduled_sard_days()
    {

        $records = ScheduledSardDaysModel::all();

        return response()->json([

            'message' => 'تم استرجاع السجلات بنجاح',
            'records' => $records

        ],200);

    }



    /*
    |--------------------------------------------------------------------------
    | Get Specific Record
    |--------------------------------------------------------------------------
    */

    public function get_special_scheduled_sard_day(Request $request)
    {

        $record = ScheduledSardDaysModel::find($request->id);

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
    | Update Record
    |--------------------------------------------------------------------------
    */

    public function update_scheduled_sard_day(Request $request)
    {

        DB::beginTransaction();

        try{

            $record = ScheduledSardDaysModel::find($request->id);

            if(!$record){

                return response()->json([

                    'message'=>'لم يتم العثور على السجل'

                ],404);

            }

            $record->update([

                'student_id'     => $request->student_id,
                'serd_day'       => $request->serd_day,
                'num_of_assigned_session'=> $request->num_of_assigned_session,
                'teacher_id'     => $request->teacher_id,
                'from_surah'     => $request->from_surah,
                'from_ayah'      => $request->from_ayah,
                'to_surah'       => $request->to_surah,
                'to_ayah'        => $request->to_ayah,
                'time_assigned'  => $request->time_assigned,
                'sard_date'      => $request->sard_date,
                'insert_date'    => $request->insert_date,

            ]);

            DB::commit();

            return response()->json([

                'message'=>'تم تحديث السجل بنجاح',
                'record'=>$record

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
    | Delete Record
    |--------------------------------------------------------------------------
    */

    public function delete_scheduled_sard_day(Request $request)
    {

        DB::beginTransaction();

        try{

            $record = ScheduledSardDaysModel::find($request->id);

            if(!$record){

                return response()->json([

                    'message'=>'لم يتم العثور على السجل'

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