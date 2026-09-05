<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddSardSessionRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateSardSessionRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SardSessionsRecordModel;

class SardSessionsRecordController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Add Record
    |--------------------------------------------------------------------------
    */

    public function add_sard_session_record(AddSardSessionRequest $request)
    {
        DB::beginTransaction();

        try {

     $record = SardSessionsRecordModel::create([
    'student_id' => $request->student_id,
    'record_sard_day_id' => $request->record_sard_day_id,
    'total_mistakes' => $request->total_mistakes,
    'total_melodies' => $request->total_melodies,
    'hesitiations_state' => $request->hesitiations_state,
    'serd_day' => $request->serd_day,
    'from_surah' => $request->from_surah,
    'from_ayah' => $request->from_ayah,
    'to_surah' => $request->to_surah,
    'to_ayah' => $request->to_ayah,
    'session_state' => $request->session_state,
    'session_number' => $request->session_number,
    'sard_date' => $request->sard_date,
    'insert_date' => $request->insert_date,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تمت إضافة سجل جلسة سرد بنجاح',
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

    public function get_all_sard_session_records()
    {

        $records = SardSessionsRecordModel::all();

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

    public function get_special_sard_session_record(IdRequest $request)
    {

        $record = SardSessionsRecordModel::find($request->id);

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

    public function update_sard_session_record(UpdateSardSessionRequest $request)
    {

        DB::beginTransaction();

        try {

            $record = SardSessionsRecordModel::find($request->id);

            if (!$record) {

                return response()->json([
                    'message' => 'لم يتم العثور على السجل'
                ], 404);

            }

            $record->update([

 'student_id' => $request->student_id,

    'record_sard_day_id' => $request->record_sard_day_id,

    'total_mistakes' => $request->total_mistakes,

    'total_melodies' => $request->total_melodies,

    'hesitiations_state' => $request->hesitiations_state,

    'serd_day' => $request->serd_day,

    'from_surah' => $request->from_surah,

    'from_ayah' => $request->from_ayah,

    'to_surah' => $request->to_surah,

    'to_ayah' => $request->to_ayah,

    'session_state' => $request->session_state,

    'session_number' => $request->session_number,

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

    public function delete_sard_session_record(IdRequest $request)
    {

        DB::beginTransaction();

        try {

            $record = SardSessionsRecordModel::find($request->id);

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