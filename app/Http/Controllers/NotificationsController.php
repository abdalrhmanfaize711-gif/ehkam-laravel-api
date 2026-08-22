<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB; //
use Illuminate\Http\Request;
use App\Models\NotificationsModel;

class NotificationsController extends Controller
{
     function add_notifications(Request $request){
        $notifications = NotificationsModel::create([
            'student_id' => $request->student_id,
            'halaqa_id' => $request->halaqa_id,
            'title' => $request->title,
            'notification_time' => $request->notification_time,
            'insert_date' => $request->insert_date,
          
        ]);
   return response()->json(['message' => 'notifications added successfully', 'notifications' => $notifications], 201);

}
  
    function update_notifications(Request $request){
        $notification = NotificationsModel::findOrfail($request->id);
       $notification->update([
        'student_id' => $request->student_id,
            'halaqa_id' => $request->halaqa_id,
            'title' => $request->title,
            'notification_time'=>$request->notification_time,
            'insert_date' => $request->insert_date,
        ]);
      
       

        return response()->json(['message' => 'notifications update successfully ', 'notification' => $notification], 201);
     
    }

 public function delete_notifications(Request $request)
{
    try {

        if (!$request->has('id')) {
            return response()->json([
                'message' => 'id is required'
            ], 400);
        }

        $notification = NotificationsModel::find($request->id);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found',
                'id' => $request->id
            ], 404);
        }

        $deleted = NotificationsModel::where('id', $request->id)->delete();

        return response()->json([
            'message' => 'تمت المعالجة',
            'deleted_rows' => $deleted,
            'database' => DB::connection()->getDatabaseName()
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'message' => $e->getMessage()
        ], 500);

    }
}
    function get_special_notifications(Request $request){
         $notifications = NotificationsModel::findOrFail($request->id);
         return  response()->json(['message' => ' delete successfully'], 201);
    }
      function get_all_notifications(){
         $notifications = NotificationsModel::all();
         return  response()->json(['message' => ' delete successfully' , 'notifications'=> $notifications ], 201);
    }
}
