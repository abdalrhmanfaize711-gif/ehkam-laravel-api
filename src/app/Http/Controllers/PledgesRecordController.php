<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddPledgeRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\IdStudentRequest;
use App\Http\Requests\Api\UpdatePledgeRequest;

use Illuminate\Http\Request;
use App\Models\PledgesRecordModel;

class PledgesRecordController extends Controller
{
    function add_pledges_record(Request $request){
        $pledges_record = PledgesRecordModel::create([
        'student_id' => $request->student_id,
        'pledge_type' => $request->pledge_type,
        'insert_date' => $request->insert_date,
        ]);
        return response()->json(['message' => 'تم إضافة التعهد بنجاح', 'pledges_record' => $pledges_record], 201);
    }

       function get_all_pledges(Request $request){
         $pledges_record = PledgesRecordModel::all();
         return  response()->json(['message' => 'تم استرجاع جميع التعهدات بنجاح', 'pledges_record' => $pledges_record], 201);
        }
    function update_pledge(Request $request){
        $record = PledgesRecordModel::findOrFail($request->id);
        $record->update([
          'student_id' => $request->student_id,
          'pledge_type' => $request->pledge_type,
          'insert_date' => $request->insert_date,
        ]);
    

        return response()->json(['message' => 'تم تحديث السجل بنجاح ', 'record' => $record], 201);
    
    }
    function delete_pledge(Request $request){
         $pledges_record = PledgesRecordModel::findOrFail($request->id);
         $pledges_record->delete();
         return  response()->json(['message' => 'تم حذف التعهد بنجاح', ], 201);
        }
    function get_special_pledge(IdStudentRequest $request){
         $record = PledgesRecordModel::where('student_id',$request->student_id)
         ->first()
         ->get();
         return  response()->json(['message' => 'تم جلب التعهد بنجاح', 'record' => $record], 201);
        }
}
