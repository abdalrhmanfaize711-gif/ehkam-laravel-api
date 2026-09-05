<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddSardRecordRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateSardRecordRequest;


use App\Models\SardRecordsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SardRecordsController extends Controller
{
    /**
     * Create Sard Plan
     */
    public function add_sard_record(AddSardRecordRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teachers,id',
            'total_assigned_juz' => 'required|integer|min:1|max:30',
            'num_of_days' => 'required|integer|min:1',
            'insert_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {

            $record = SardRecordsModel::create([
                'student_id' => $request->student_id,
                'teacher_id' => $request->teacher_id,
                'total_assigned_juz' => $request->total_assigned_juz,
                'num_of_days' => $request->num_of_days,
                'insert_date' => $request->insert_date,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء خطة سرد.',
                'data' => $record
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Get All Sard Records
     */
    public function get_all_sard_records()
    {
        $records = SardRecordsModel::with([
            'student.user',
            'teacher.user'
        ])->get();

        return response()->json([
            'status' => true,
            'data' => $records
        ]);
    }

    /**
     * Get One Record
     */
    public function get_sard_record(IdRequest $request)
    {
       

        $record = SardRecordsModel::with([
            'student.user',
            'teacher.user'
        ])->find($request->id);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $record->id,
                'student_id' => $record->student_id,
                'teacher_id' => $record->teacher_id,
                'serd_schedule_id' => $record->serd_schedule_id,
                'total_assigned_juz' => $record->total_assigned_juz,
                'num_of_days' => $record->num_of_days,
                'insert_date' => $record->insert_date,
            ]
            
        ]);
    }

    /**
     * Update Record
     */
   public function update_sard_record(UpdateSardRecordRequest $request)
{
    $validated = $request->validated();

    DB::beginTransaction();

    try {

        $record = SardRecordsModel::findOrFail($validated['id']);

        $record->update([
            'student_id' => $validated['student_id'],
            'teacher_id' => $validated['teacher_id'],
            'total_assigned_juz' => $validated['total_assigned_juz'],
            'num_of_days' => $validated['num_of_days'],
            'insert_date' => $validated['insert_date'],
        ]);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث خطة سرد بنجاح.',
            'data' => $record
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Delete Record
     */
    public function delete_sard_record(IdRequest $request)
    {
       

        DB::beginTransaction();

        try {

            $record = SardRecordsModel::findOrFail($request->id);

            $record->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف خطة سرد بنجاح.'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ],500);
        }
    }
}