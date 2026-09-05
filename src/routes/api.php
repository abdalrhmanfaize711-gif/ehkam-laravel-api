<?php
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use MohamedSabil83\LaravelHijrian\Facades\Hijrian;
use App\Http\Controllers\RecoredExamsController;
use App\Http\Controllers\RecordSardDaysController;
use App\Http\Controllers\SardSessionsRecordController;
use App\Http\Controllers\ScheduledExamsController;
use App\Http\Controllers\ScheduledSardStageController;
use App\Http\Controllers\SerdSchedulesController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TasshehRecoredController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AdditionRecordsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EtqanRecordController;
use App\Http\Controllers\HalaqatController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PledgesRecordController;
use App\Http\Controllers\RecordPlanYearsController;
use App\Http\Controllers\RecordSardStagesController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChangeStudentStageController;
use App\Http\Controllers\SardScheduleController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ScheduledSardDaysController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\TeacherProfileController;
use App\Http\Controllers\AttendancesController;
use App\Http\Controllers\StudentRecordProfileController;
use App\Http\Controllers\SardRecordsController;
use App\Models\User;


Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');


Route::post('/loginStudent', [AuthController::class, 'loginStudent'])->middleware('throttle:login');



Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

           
           //ScheduledSardDaysController
           Route::get('/get_all_scheduled_sard_days',[ScheduledSardDaysController::class, 'get_all_scheduled_sard_days']);
           
           //student
           Route::get('/get_record_date', [StudentController::class, 'getDatesRecords']);
           Route::post('/add_student', [StudentController::class, 'Add_student']);
           Route::post('/get_records_by_date', [StudentController::class, 'getStudentRecordsByDate']);
           Route::post('/add_student_json', [StudentController::class, 'Add_students']);
           Route::put('/update_record_of_STD', [StudentController::class, 'update_record_of_STD']);
           Route::put('/update_student', [StudentController::class, 'update_student']);
           Route::delete('/delete_student', [StudentController::class, 'delete_student']);
           Route::put('/Delete_STD_from_halaqa', [StudentController::class, 'Delete_STD_from_halaqa']);
           
           //teacher
           Route::get('/get_all_teachers', [TeacherController::class, 'get_all_teachers']);
           Route::post('/add_teacher', [TeacherController::class, 'add_teacher']);
           Route::post('/add_teacher_json', [TeacherController::class, 'add_teachers']);
           Route::put('/update_teacher', [TeacherController::class, 'update_teacher']);
           Route::delete('/delete_teacher', [TeacherController::class, 'delete_teacher']);
           
           //year plan
           Route::post('/add_record_plan_years', [RecordPlanYearsController::class, 'add_record_plan_years']);
           Route::post('/get_record_plan_years', [RecordPlanYearsController::class, 'get_special_record_plan_years']);
           Route::put('/update_record_plan_years', [RecordPlanYearsController::class, 'update_record_plan_years']);
           Route::delete('/delete_record_plan_years', [RecordPlanYearsController::class, 'delete_record_plan_years']);
           Route::get('/get_plan_years', [RecordPlanYearsController::class, 'get_record_plan_years']);
           
           
           //teacher
           
           //year plan
           
           
           // Halaqat Routes
           Route::post('/add_halaqat', [HalaqatController::class, 'add_halaqat']);
           Route::post('/get_special_halaqa', [HalaqatController::class, 'get_special_halaqa']);
           Route::put('/update_halaqa', [HalaqatController::class, 'update_halaqa']);
           Route::delete('/delete_halaqa', [HalaqatController::class, 'delete_halaqa']);
           
           // Notifications Routes
           Route::get('/get_notifications', [NotificationsController::class, 'get_all_notifications']);
           Route::get('/get_date_notifications', [NotificationsController::class, 'getLastFourNotificationDates']);
           Route::post('/get_special_notifications', [NotificationsController::class, 'get_special_notifications']);
           Route::put('/update_notifications', [NotificationsController::class, 'update_notifications']);
           Route::delete('/delete_notifications', [NotificationsController::class, 'delete_notifications']);
           
           // Pledges Records Routes
           Route::get('/get_all_pledges', [PledgesRecordController::class, 'get_all_pledges']);
           Route::post('/add_pledges_record', [PledgesRecordController::class, 'add_pledges_record']);
           Route::post('/get_special_pledge', [PledgesRecordController::class, 'get_special_pledge']);
           Route::put('/update_pledge', [PledgesRecordController::class, 'update_pledge']);
           Route::delete('/delete_pledge', [PledgesRecordController::class, 'delete_pledge']);
           
           // Attendances Routes
           Route::post('/get_absent', [AttendancesController::class, 'getAbsentStudentsByDate']);
           Route::get('get_fridays_date', [AttendancesController::class, 'getLastFourAttendanceDates']);
           Route::post( 'last_Fridays_absent', [AttendancesController::class, 'getAbsentStudentByDate']);
           Route::post('/get_num_attendances', [AttendancesController::class, 'get_special_attendances']);
           Route::post('/get_student_record_profile',[StudentRecordProfileController::class, 'getStudentRecordProfile']);
           Route::get('/get_today_attendance_percentage', [AttendancesController::class, 'getTodayAttendancePercentage']);
           Route::post('/add_late_attendance', [AttendancesController::class, 'addLateAttendance']);
           
           // Record Plan Years Routes
           
           // Record Sard Stages Routes
           Route::post('/add_record_sard_stages', [RecordSardStagesController::class, 'store']);
            
           //sard schdued 
           Route::post('/add_sard',[SardScheduleController::class,'store']);
           Route::get('/get_sard-schedules',[SardScheduleController::class,'index']);
           Route::post('/get_one_sard-schedules',[SardScheduleController::class,'show']);
           Route::Put('/update_sard-schedules',[SardScheduleController::class,'update']);
           Route::delete('/delete_sard-schedules',[SardScheduleController::class,'destroy']);
           
           //reports controller 
           Route::post('/reports_students',[ReportsController::class, 'studentsReport']);
           
           //get_one_user
           Route::post('/get_one_user', [UserController::class, 'get_one_user']);
           
           // Scheduled Exams Routes
           Route::post('/add_scheduled_exam', [ScheduledExamsController::class, 'add_scheduled_exam']);
           Route::put('/update_scheduled_exams', [ScheduledExamsController::class, 'update_scheduled_exams']);
           Route::delete('/delete_scheduled_exams', [ScheduledExamsController::class, 'delete_scheduled_exams']);
           
           // Scheduled Sard Stage Routes
           
           // Sard Schedule Routes

Route::post('/add_sard', [SardScheduleController::class,'store']);
Route::get('/get_all_serd_schedule', [SardScheduleController::class,'index']);
Route::post('/get_special_serd_schedule', [SardScheduleController::class,'show']);
Route::put('/update_serd_schedule', [SardScheduleController::class,'update']);
Route::delete('/delete_serd_schedule', [SardScheduleController::class,'destroy']);
          
                   //SardRecordsController
     
          
           // Recored Exams Routes
     
           
           //RecordSardDaysController
            
});


Route::middleware(['auth:sanctum', 'role:admin,teacher'])->group(function () {

    Route::get('/get_all_student', [StudentController::class, 'get_all_student']);
    Route::get('/get_all_halaqat', [HalaqatController::class, 'get_all_halaqat']);
    Route::get('/get_all_attendances_TCH', [AttendancesController::class, 'get_all_attendances_TCH']);
    Route::get('/get_all_attendances_STD', [AttendancesController::class, 'get_all_attendances_STD']);
    Route::post('/add_attendances', [AttendancesController::class, 'add_attendances']);
    Route::post('/get_teacher_profile',[TeacherProfileController::class, 'getInfoPresnal']);
    Route::get('/get_all_scheduled_exams', [ScheduledExamsController::class, 'get_all_scheduled_exams']);
    Route::post('/get_special_scheduled_exams', [ScheduledExamsController::class, 'get_special_scheduled_exams']);
    Route::get('/get_all_scheduled_sard_stage', [ScheduledSardStageController::class, 'get_all_scheduled_sard_stage']);
    Route::post('/add_scheduled_sard_stage', [ScheduledSardStageController::class, 'add_scheduled_sard_stage']);
    Route::post('/get_special_scheduled_sard_stage', [ScheduledSardStageController::class, 'get_special_scheduled_sard_stage']);
    Route::put('/update_scheduled_sard_stage', [ScheduledSardStageController::class, 'update_scheduled_sard_stage']);
    Route::delete('/delete_scheduled_sard_stage', [ScheduledSardStageController::class, 'delete_scheduled_sard_stage']);
    Route::get('/get_all_serd_schedule', [SardScheduleController::class, 'index']);
    Route::post('/get_special_serd_schedule', [SardScheduleController::class, 'show']);
    Route::post('/add_sard_record', [SardRecordsController::class, 'add_sard_record']);
    Route::get('/get_all_sard_records', [SardRecordsController::class, 'get_all_sard_records']);
    Route::post('/get_sard_record', [SardRecordsController::class, 'get_sard_record']);
    Route::put('/update_sard_record', [SardRecordsController::class, 'update_sard_record']);
    Route::delete('/delete_sard_record', [SardRecordsController::class, 'delete_sard_record']);
    Route::get('/get_all_record_exam', [RecoredExamsController::class, 'get_all_record_exam']);
    Route::post('/add_record_exam', [RecoredExamsController::class, 'add_record_exam']);
    Route::post('/get_special_record_exam', [RecoredExamsController::class, 'get_special_record_exam']);
    Route::put('/update_record_exam', [RecoredExamsController::class, 'update_record_exam']);
    Route::delete('/delete_record_exam', [RecoredExamsController::class, 'delete_record_exam']);
    Route::post('/add_record-sard-days', [RecordSardDaysController::class, 'add_record_sard_day']);
    Route::get('/record-sard-days', [RecordSardDaysController::class, 'get_all_record_sard_days']);
    Route::post('/get_one_record-sard-days', [RecordSardDaysController::class, 'get_special_record_sard_day']);
    Route::put('/update_record-sard-days', [RecordSardDaysController::class, 'update_record_sard_day']);
    Route::delete('/record-sard-days', [RecordSardDaysController::class, 'delete_record_sard_day']);
});


Route::middleware(['auth:sanctum', 'role:admin,student', 'student.scope'])->group(function () {

    Route::post('/get_Info_profile', [StudentProfileController::class, 'getInfoPresnal']);
});


Route::middleware(['auth:sanctum', 'role:admin,teacher', 'teacher.scope'])->group(function () {

     //student 

      // Addition Records Routes
      Route::get('/get_record', [AdditionRecordsController::class, 'get_all_record']);
      Route::post('/add_record', [AdditionRecordsController::class, 'add_addition_records']);
      Route::delete('/delete', [AdditionRecordsController::class, 'delete']);
      
      // Etqan Records Routes
      Route::get('/get_all_etqan', [EtqanRecordController::class, 'get_all_record']);
      Route::post('/add_etqan_records', [EtqanRecordController::class, 'add_etqan_records']);
      Route::delete('/delete_etqan', [EtqanRecordController::class, 'delete']);

      //SardRecordsController

     
      // Recored Exams Routes


      //RecordSardDaysController
       
      // Tassheh Record Routes
      Route::post('/add_tassheh_record', [TasshehRecoredController::class, 'add_tassheh_record']);
      Route::post('/get_special_tassheh_record', [TasshehRecoredController::class, 'get_special_tassheh_record']);
      Route::put('/update_tassheh_record', [TasshehRecoredController::class, 'update_tassheh_record']);
      Route::delete('/delete_tassheh_record', [TasshehRecoredController::class, 'delete_tassheh_record']);

      
      // Sard Sessions (SardDay) Routes
      Route::post( '/add_sard_session',[SardSessionsRecordController::class, 'add_sard_session_record']);
      Route::get('/get_all_sard_sessions',[SardSessionsRecordController::class, 'get_all_sard_session_records']);
      Route::post('/get_special_sard_session',[SardSessionsRecordController::class, 'get_special_sard_session_record']);
      Route::put('/update_sard_session',[SardSessionsRecordController::class, 'update_sard_session_record']);
      Route::delete('/delete_sard_session',[SardSessionsRecordController::class, 'delete_sard_session_record']);

     //RecoredExamsController
      Route::get('/surprise_exam_statistics', [RecoredExamsController::class, 'surprise_exam_statistics']);

      // Halaqat Routes

     // Attendances Controller

    // Scheduled Sard Stage Routes




    
});

Route::post('/test-json', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'is_json' => $request->isJson(),
        'content' => $request->getContent(),
        'json' => $request->json()->all(),
        'request' => $request->request->all(),
        'all' => $request->all(),
    ]);
});