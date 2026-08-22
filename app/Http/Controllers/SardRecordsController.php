<?php

namespace App\Http\Controllers;


use App\Models\SardRecordsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SardRecordsController extends Controller
{
    /**
     * Create Sard Plan
     */
    public function add_sard_record(Request $request)
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
                'message' => 'Sard plan created successfully.',
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
    public function get_sard_record(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:sard_record,id'
        ]);

        $record = SardRecordsModel::with([
            'student.user',
            'teacher.user'
        ])->find($request->id);

        return response()->json([
            'status' => true,
            'data' => $record
        ]);
    }

    /**
     * Update Record
     */
    public function update_sard_record(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:sard_record,id',
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
            ],422);
        }

        DB::beginTransaction();

        try {

            $record = SardRecordsModel::findOrFail($request->id);

            $record->update([
                'student_id' => $request->student_id,
                'teacher_id' => $request->teacher_id,
                'total_assigned_juz' => $request->total_assigned_juz,
                'num_of_days' => $request->num_of_days,
                'insert_date' => $request->insert_date,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sard plan updated successfully.',
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
     * Delete Record
     */
    public function delete_sard_record(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:sard_record,id'
        ]);

        DB::beginTransaction();

        try {

            $record = SardRecordsModel::findOrFail($request->id);

            $record->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sard plan deleted successfully.'
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