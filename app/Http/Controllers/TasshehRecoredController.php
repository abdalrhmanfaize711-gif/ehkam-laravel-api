<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\TasshehRecoredModel;

class TasshehRecoredController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Add Tassheh Records
    |--------------------------------------------------------------------------
    |
    | - Supports multiple students.
    | - Prevents duplicate records for the same student on the same date.
    | - Uses Database Transaction.
    |
    |--------------------------------------------------------------------------
    */

    public function add_tassheh_record(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'records' => 'required|array|min:1',

            'records.*.student_id' => 'required|exists:students,id',

            'records.*.tassheh_halaqa_id' => 'nullable|integer',

            'records.*.Is_corrected' => 'required|boolean',

            'records.*.insert_date' => 'required|date',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {

            $added = [];

            $alreadyExists = [];

            foreach ($request->records as $record) {

                /*
                |--------------------------------------------------------------------------
                | Check if student already has correction in same day
                |--------------------------------------------------------------------------
                */

                $exists = TasshehRecoredModel::where(
                    'student_id',
                    $record['student_id']
                )
                ->whereDate(
                    'insert_date',
                    $record['insert_date']
                )
                ->exists();

                if ($exists) {

                    $alreadyExists[] = [

                        'student_id' => $record['student_id'],

                        'insert_date' => $record['insert_date'],

                        'message' => 'تم تصحيح هذا الطالب بالفعل في هذا التاريخ.'

                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Create Record
                |--------------------------------------------------------------------------
                */

                $tasshehRecord = TasshehRecoredModel::create([

                    'student_id' => $record['student_id'],

                     'halaqa_id' => $record['halaqa_id'],

                    'Is_corrected' => $record['Is_corrected'],

                    'insert_date' => $record['insert_date']

                ]);

                $added[] = $tasshehRecord;
            }

            DB::commit();

            return response()->json([

                'message' => 'تم معالجة سجلات التصحيح بنجاح.',

                'added_count' => count($added),

                'exists_count' => count($alreadyExists),

                'added_records' => $added,

                'already_exists' => $alreadyExists

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
    | Update Tassheh Record
    |--------------------------------------------------------------------------
    */

    public function update_tassheh_record(Request $request)
    {
        $request->validate([

            'id' => 'required|exists:tassheh_recoreds,id',

            'student_id' => 'required|exists:students,id',

            'Is_corrected' => 'required|boolean',

            'insert_date' => 'required|date',

        ]);

        DB::beginTransaction();

        try {

            $record = TasshehRecoredModel::findOrFail(
                $request->id
            );

            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate after update
            |--------------------------------------------------------------------------
            */

            $exists = TasshehRecoredModel::where(
                'student_id',
                $request->student_id
            )
            ->whereDate(
                'insert_date',
                $request->insert_date
            )
            ->where(
                'id',
                '!=',
                $request->id
            )
            ->exists();

            if ($exists) {

                DB::rollBack();

                return response()->json([

                    'message' => 'هذا الطالب لديه بالفعل سجل تصحيح في هذا التاريخ.'

                ], 409);
            }

            $record->update([

                'student_id' => $request->student_id,

                'halaqa_id' => $record->tassheh_halaqa_id,

                'Is_corrected' => $request->Is_corrected,

                'insert_date' => $request->insert_date,

            ]);

            DB::commit();

            return response()->json([

                'message' => 'تم تحديث سجل التصحيح بنجاح.',

                'tassheh_record' => $record

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
    | Delete Tassheh Record
    |--------------------------------------------------------------------------
    */

    public function delete_tassheh_record(Request $request)
    {
        $request->validate([

            'id' => 'required|exists:tassheh_recoreds,id'

        ]);

        DB::beginTransaction();

        try {

            $record = TasshehRecoredModel::findOrFail(
                $request->id
            );

            $record->delete();

            DB::commit();

            return response()->json([

                'message' => 'تم حذف سجل التصحيح بنجاح.'

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
    | Get One Record
    |--------------------------------------------------------------------------
    */

    public function get_special_tassheh_record(Request $request)
    {
    
        $record_today = TasshehRecoredModel::
        whereDate('insert_date' ,$request->insert_date)->get();
        return response()->json([

            'message' => 'Success',

            'tassheh_record' => $record_today

        ], 200);
    }
}