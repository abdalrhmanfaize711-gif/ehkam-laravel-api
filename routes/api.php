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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/loginStudent', [AuthController::class, 'loginStudent']);
Route::middleware('auth:api')->group(function () {

    Route::get('/profile', [AuthController::class, 'profile']);

    Route::post('/logout', [AuthController::class, 'logout']);

});
// Admin Routes
Route::post('/add_admin', [AdminController::class, 'set_data_admine']);
Route::delete('/delete_admin', [AdminController::class, 'delete_admin']);
// Addition Records Routes
Route::get('/get_record', [AdditionRecordsController::class, 'get_all_record']);
Route::post('/add_record', [AdditionRecordsController::class, 'add_addition_records']);
Route::post('/get_special_record', [AdditionRecordsController::class, 'get_special_record']);
Route::put('/update_record', [AdditionRecordsController::class, 'update_record']);
Route::delete('/delete', [AdditionRecordsController::class, 'delete']);
//student
Route::get('/get_all_student', [StudentController::class, 'get_all_student']);
Route::post('/add_student', [StudentController::class, 'Add_student']);
Route::post('/add_student_json', [StudentController::class, 'Add_students']);
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
Route::get('/get_record_plan_years', [RecordPlanYearsController::class, 'get_special_record_plan_years']);
Route::put('/update_record_plan_years', [RecordPlanYearsController::class, 'update_record_plan_years']);
Route::delete('/delete_record_plan_years', [RecordPlanYearsController::class, 'delete_record_plan_years']);
Route::get('/get_plan_years', [RecordPlanYearsController::class, 'get_record_plan_years']);


// Etqan Records Routes
Route::get('/get_all_etqan', [EtqanRecordController::class, 'get_all_record']);
Route::post('/add_etqan_records', [EtqanRecordController::class, 'add_etqan_records']);
Route::post('/get_special_etqan', [EtqanRecordController::class, 'get_special_record']);
Route::put('/update_etqan', [EtqanRecordController::class, 'update_record']);
Route::delete('/delete_etqan', [EtqanRecordController::class, 'delete']);

// Halaqat Routes
Route::get('/get_all_halaqat', [HalaqatController::class, 'get_all_halaqat']);
Route::post('/add_halaqat', [HalaqatController::class, 'add_halaqat']);
Route::post('/get_special_halaqa', [HalaqatController::class, 'get_special_halaqa']);
Route::put('/update_halaqa', [HalaqatController::class, 'update_halaqa']);
Route::delete('/delete_halaqa', [HalaqatController::class, 'delete_halaqa']);

// Notifications Routes
Route::get('/get_notifications', [NotificationsController::class, 'get_all_notifications']);
Route::post('/add_notifications', [NotificationsController::class, 'add_notifications']);
Route::post('/get_special_notifications', [NotificationsController::class, 'get_special_notifications']);
Route::put('/update_notifications', [NotificationsController::class, 'update_notifications']);
Route::delete('/delete_notifications', [NotificationsController::class, 'delete_notifications']);

// Pledges Records Routes
Route::get('/get_all_pledges', [PledgesRecordController::class, 'get_all_pledges']);
Route::post('/add_pledges_record', [PledgesRecordController::class, 'add_pledges_record']);
Route::post('/get_special_pledge', [PledgesRecordController::class, 'get_special_pledge']);
Route::put('/update_pledge', [PledgesRecordController::class, 'update_pledge']);
Route::delete('/delete_pledge', [PledgesRecordController::class, 'delete_pledge']);

// Record Plan Years Routes
Route::get('/get_record_plan_years', [RecordPlanYearsController::class, 'get_record_plan_years']);
Route::post('/add_record_plan_years', [RecordPlanYearsController::class, 'add_record_plan_years']);
Route::post('/get_special_pledge', [RecordPlanYearsController::class, 'get_special_pledge']);
Route::put('/update_record_plan_years', [RecordPlanYearsController::class, 'update_record_plan_years']);
Route::delete('/delete_record_plan_years', [RecordPlanYearsController::class, 'delete_record_plan_years']);

// Record Sard Stages Routes
Route::get('/get_all_record_sard_stages', [RecordSardStagesController::class, 'get_all_record_sard_stages']);
Route::post('/add_record_sard_stages', [RecordSardStagesController::class, 'store']);
Route::post('/get_special_record_sard_stages', [RecordSardStagesController::class, 'get_special_record_sard_stages']);
Route::put('/update_record_sard_stages', [RecordSardStagesController::class, 'update_record_sard_stages']);
Route::delete('/delete_record_sard_stage', [RecordSardStagesController::class, 'delete_record_sard_stages']);

// Recored Exams Routes
Route::get('/get_all_record_exam', [RecoredExamsController::class, 'get_all_record_exam']);
Route::post('/add_record_exam', [RecoredExamsController::class, 'add_record_exam']);
Route::post('/get_special_record_exam', [RecoredExamsController::class, 'get_special_record_exam']);
Route::put('/update_record_exam', [RecoredExamsController::class, 'update_record_exam']);
Route::delete('/delete_record_exam', [RecoredExamsController::class, 'delete_record_exam']);

// Sard Sessions (SardDay) Routes


Route::post( '/add_sard_session',[SardSessionsRecordController::class, 'add_sard_session_record']);
Route::get('/get_all_sard_sessions',[SardSessionsRecordController::class, 'get_all_sard_session_records']);
Route::post('/get_special_sard_session',[SardSessionsRecordController::class, 'get_special_sard_session_record']);
Route::put('/update_sard_session',[SardSessionsRecordController::class, 'update_sard_session_record']);
Route::delete('/delete_sard_session',[SardSessionsRecordController::class, 'delete_sard_session_record']);

// Scheduled Exams Routes
Route::get('/get_all_scheduled_exams', [ScheduledExamsController::class, 'get_all_scheduled_exams']);
Route::post('/add_scheduled_exam', [ScheduledExamsController::class, 'add_scheduled_exam']);
Route::post('/get_special_scheduled_exams', [ScheduledExamsController::class, 'get_special_scheduled_exams']);
Route::put('/update_scheduled_exams', [ScheduledExamsController::class, 'update_scheduled_exams']);
Route::delete('/delete_scheduled_exams', [ScheduledExamsController::class, 'delete_scheduled_exams']);

// Scheduled Sard Stage Routes
Route::get('/get_all_scheduled_sard_stage', [ScheduledSardStageController::class, 'get_all_scheduled_sard_stage']);
Route::post('/add_scheduled_sard_stage', [ScheduledSardStageController::class, 'add_scheduled_sard_stage']);
Route::post('/get_special_scheduled_sard_stage', [ScheduledSardStageController::class, 'get_special_scheduled_sard_stage']);
Route::put('/update_scheduled_sard_stage', [ScheduledSardStageController::class, 'update_scheduled_sard_stage']);
Route::delete('/delete_scheduled_sard_stage', [ScheduledSardStageController::class, 'delete_scheduled_sard_stage']);

// Serd Schedules Routes
Route::get('/get_all_serd_schedule', [SerdSchedulesController::class, 'get_all_serd_schedule']);
Route::post('/add_serd_schedule', [SerdSchedulesController::class, 'add_serd_schedule']);
Route::post('/get_special_serd_schedule', [SerdSchedulesController::class, 'get_special_serd_schedule']);
Route::put('/update_serd_schedule', [SerdSchedulesController::class, 'update_serd_schedule']);
Route::delete('/delete_serd_schedule', [SerdSchedulesController::class, 'delete_serd_schedule']);

// Tassheh Record Routes
Route::post('/add_tassheh_record', [TasshehRecoredController::class, 'add_tassheh_record']);
Route::post('/get_tassheh_record', [TasshehRecoredController::class, 'get_tassheh_records']);
Route::put('/update_tassheh_record', [TasshehRecoredController::class, 'update_tassheh_record']);
Route::delete('/delete_tassheh_record', [TasshehRecoredController::class, 'delete_tassheh_record']);

// Regions
Route::get('/get_all_region', [RegionController::class, 'get_all_region']);         // جلب جميع المناطق
Route::post('/add_region', [RegionController::class, 'add_region']);            // إضافة منطقة جديدة
Route::post('/get_special_region', [RegionController::class, 'get_special_region']); // عرض منطقة واحدة
Route::put('/update_region', [RegionController::class, 'update_region']);          // تعديل منطقة
Route::delete('/delete_region', [RegionController::class, 'delete_region']);       // حذف منطقة

Route::post('/add_record-sard-days', [RecordSardDaysController::class, 'add_record_sard_day']);
Route::get('/record-sard-days', [RecordSardDaysController::class, 'get_all_record_sard_days']);
Route::post('/get_one_record-sard-days', [RecordSardDaysController::class, 'get_special_record_sard_day']);
Route::put('/update_record-sard-days', [RecordSardDaysController::class, 'update_record_sard_day']);
Route::delete('/record-sard-days', [RecordSardDaysController::class, 'delete_record_sard_day']);
Route::get('/record-sard-days', [RecordSardDaysController::class, 'get_all_record_sard_days']);
Route::post('/get_one_user', [UserController::class, 'get_one_user']);
Route::get('/surprise_exam_statistics', [RecoredExamsController::class, 'surprise_exam_statistics']);


//sard schdued 
Route::post('/add_sard',[SardScheduleController::class,'store']);
Route::get('/get_sard-schedules',[SardScheduleController::class,'index']);
Route::post('/get_one_sard-schedules',[SardScheduleController::class,'show']);
Route::post('/update_sard-schedules',[SardScheduleController::class,'update']);
Route::delete('/delete_sard-schedules',[SardScheduleController::class,'destroy']);


//reports controller 
Route::post('/reports_students',[ReportsController::class, 'studentsReport']);
Route::post('/reports_teachers',[ReportsController::class, 'teachersReport']);
Route::get('/get_all_scheduled_sard_days',[ScheduledSardDaysController::class, 'get_all_scheduled_sard_days']);
Route::post('/get_Info_profile', [StudentProfileController::class, 'getInfoPresnal']);
Route::post('/get_teacher_profile',[TeacherProfileController::class, 'getInfoPresnal']);

// Attendances Routes
Route::get('/get_all_attendances', [AttendancesController::class, 'get_all_attendances']);
Route::post('/add_attendances', [AttendancesController::class, 'add_attendances']);
Route::post('/get_special_lattendances', [AttendancesController::class, 'get_special_lattendances']);
Route::post('/get_absent', [AttendancesController::class, 'getAbsentStudentsByDate']);
Route::get('get_fridays_date', [AttendancesController::class, 'getLastFourAttendanceDates']);
Route::post( 'last_Fridays_absent', [AttendancesController::class, 'getAbsentStudentByDate']);
Route::post('/get_num_attendances', [AttendancesController::class, 'get_special_attendances']);
Route::post('/get_student_record_profile',[StudentRecordProfileController::class, 'getStudentRecordProfile']);
Route::get('/get_today_attendance_percentage', [AttendancesController::class, 'getTodayAttendancePercentage']);
Route::post('/add_late_attendance', [AttendancesController::class, 'addLateAttendance']);

Route::post('/add_sard_record', [SardRecordsController::class, 'add_sard_record']);
Route::get('/get_all_sard_records', [SardRecordsController::class, 'get_all_sard_records']);
Route::post('/get_sard_record', [SardRecordsController::class, 'get_sard_record']);
Route::post('/update_sard_record', [SardRecordsController::class, 'update_sard_record']);
Route::post('/delete_sard_record', [SardRecordsController::class, 'delete_sard_record']);