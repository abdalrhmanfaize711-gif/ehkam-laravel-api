<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddNotificationRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateNotificationRequest;
use Illuminate\Support\Facades\DB; //
use Illuminate\Http\Request;
use App\Models\NotificationsModel;

class NotificationsController extends Controller
{
   public function getLastFourNotificationDates()
{
    $dates = DB::table('notifications')
        ->select('insert_date')
        ->distinct()
        ->orderByDesc('insert_date')
        ->limit(6)
        ->pluck('insert_date');

    return response()->json([
        'success' => true,
        'dates' => $dates
    ], 200);
}

     function add_notifications(Request $request){
        $notifications = NotificationsModel::create([
            'student_id' => $request->student_id,
            'halaqa_id' => $request->halaqa_id,
            'title' => $request->title,
            'notification_time' => $request->notification_time,
            'insert_date' => $request->insert_date,
          
        ]);
   return response()->json(['message' => 'تم إضافة الإشعار بنجاح', 'notifications' => $notifications], 201);

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
      
       

        return response()->json(['message' => 'تم تحديث بيانات الإشعار بنجاح', 'notification' => $notification], 201);
     
    }

 public function delete_notifications(IdRequest $request)
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
                'message' => 'لم يتم العثور على الإشعار بالمعرف المحدد',
                'id' => $request->id
            ], 404);
        }

        $deleted = NotificationsModel::where('id', $request->id)->delete();

        return response()->json([
            'message' => 'تمت المعالجة',
            'deleted_rows' => $deleted,
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
         return  response()->json(['message' => 'تم استرجاع جميع الإشعارات بنجاح', 'notifications'=> $notifications ], 201);
    }
}
