<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddScheduledSardStageRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateScheduledSardStageRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ScheduledSardStageModel;

class ScheduledSardStageController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Add Scheduled Sard Stage
    |--------------------------------------------------------------------------
    */

    public function add_scheduled_sard_stage(AddScheduledSardStageRequest $request)
    {
        DB::beginTransaction();

        try {

            $record = ScheduledSardStageModel::create([

                'student_id' => $request->student_id,
                'teacher_id' => $request->teacher_id,
                'sard_type' => $request->sard_type,
                'sard_date' => $request->sard_date,
                'insert_date' => $request->insert_date,

            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم إضافة مرحلة سارد المجدولة بنجاح',
                'record' => $record
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
    | Get All Records
    |--------------------------------------------------------------------------
    */

    public function get_all_scheduled_sard_stage()
    {

        $records = ScheduledSardStageModel::all();

        return response()->json([
            'message' => 'تم استرجاع السجلات بنجاح',
            'records' => $records
        ], 200);

    }

    /*
    |--------------------------------------------------------------------------
    | Get Specific Record
    |--------------------------------------------------------------------------
    */

    public function get_special_scheduled_sard_stage(IdRequest $request)
    {

        $record = ScheduledSardStageModel::find($request->id);

        if (!$record) {

            return response()->json([
                'message' => 'لم يتم العثور على السجل'
            ], 404);

        }

        return response()->json([
            'message' => 'تم استرجاع السجل بنجاح',
            'record' => $record
        ], 200);

    }

    /*
    |--------------------------------------------------------------------------
    | Update Record
    |--------------------------------------------------------------------------
    */

    public function update_scheduled_sard_stage(UpdateScheduledSardStageRequest $request)
    {

        DB::beginTransaction();

        try {

            $record = ScheduledSardStageModel::find($request->id);

            if (!$record) {

                return response()->json([
                    'message' => 'لم يتم العثور على السجل'
                ], 404);

            } 

            $record->update([

                'student_id' => $request->student_id,
                'teacher_id' => $request->teacher_id,
                'sard_type' => $request->sard_type,
                'sard_date' => $request->sard_date,
                'insert_date' => $request->insert_date,

            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم تحديث السجل بنجاح',
                'record' => $record
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Delete Record
    |--------------------------------------------------------------------------
    */

    public function delete_scheduled_sard_stage(IdRequest $request)
    {

        DB::beginTransaction();

        try {

            $record = ScheduledSardStageModel::find($request->id);

            if (!$record) {

                return response()->json([
                    'message' => 'لم يتم العثور على السجل'
                ], 404);

            }

            $record->delete();

            DB::commit();

            return response()->json([
                'message' => 'تم حذف السجل بنجاح'
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);

        }

    }

}