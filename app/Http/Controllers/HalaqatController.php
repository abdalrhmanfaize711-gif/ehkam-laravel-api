<?php

namespace App\Http\Controllers;

use App\Models\StudentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\HalaqatModel;
use Carbon\Carbon;
class HalaqatController extends Controller
{
   public function add_halaqat(Request $request)
{
    $halaqatData = $request->has('halaqat')
        ? $request->halaqat
        : [$request->all()];

    $result = [];

    foreach ($halaqatData as $data) {

        $halaqa = HalaqatModel::create([
            'max_students' => $data['max_students'],
            'teacher_id'   => $data['teacher_id'],
            'insert_date'  => Carbon::now(),
            'halaqa_type'  => $data['halaqa_type'],
        ]);

        $result[] = $halaqa;
    }

    return response()->json([
        'message'  => 'تم إضافة الحلقة/الحلقات بنجاح',
        'halaqat'  => $result,
    ], 201);
}


    function get_all_halaqat(Request $request){
         $halaqat = HalaqatModel::all();
         return  response()->json(['message' => ' gtting successfully', 'halaqat' => $halaqat], 201);
        }

    function update_halaqa(Request $request){
        $halaqat = HalaqatModel::findOrfail($request->id);
       $halaqat->update([
        'max_students' => $request->max_students,
        'teacher_id' => $request->teacher_id,
        'halaqa_type' => $request->halaqa_type
        ]);
      
       

        return response()->json(['message' => 'تم تحديث بيانات الحلقة بنجاح', 'halaqat' => $halaqat], 201);
     
    }

    public function delete_halaqa(Request $request)
{
    $request->validate([
        'id' => 'required|exists:halaqats,id',
    ]);

    DB::beginTransaction();

    try {

        $halaqa = HalaqatModel::findOrFail($request->id);

        /*
        |--------------------------------------------------------------------------
        | إزالة الطلاب من الحلقة
        |--------------------------------------------------------------------------
        */

        StudentModel::where('halaqa_id', $halaqa->id)
            ->update([
                'halaqa_id' => null,
            ]);

        /*
        |--------------------------------------------------------------------------
        | حذف الحلقة
        |--------------------------------------------------------------------------
        */

        $halaqa->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحلقة بنجاح، وتمت إزالة جميع الطلاب منها.',
        ], 200);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

    function get_special_halaqa(Request $request){
         $halaqa = HalaqatModel::findOrFail($request->id);
         return  response()->json(['message' => 'تم الحذف بنجاح ', 'halaqa' => $halaqa], 201);
    }
 
}
