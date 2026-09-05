# Ehkam API Validation

ضع هذه الملفات داخل:

app/Http/Requests/Api/

الفكرة:
كل Endpoint يستقبل بيانات من Flutter يستعمل FormRequest خاص به.
الـ FormRequest يتحقق من البيانات قبل دخول Controller.

مثال:

use App\Http\Requests\Api\AddStudentRequest;

public function add_student(AddStudentRequest $request)
{
    $validated = $request->validated();

    // استخدم $validated فقط
}

## الملفات الأساسية

- ApiFormRequest.php: الشكل الموحد لخطأ 422.
- LoginRequest.php: تسجيل دخول admin/teacher.
- LoginStudentRequest.php: تسجيل دخول الطالب.
- AddStudentRequest.php / AddStudentsRequest.php / UpdateStudentRequest.php
- AddTeacherRequest.php / AddTeachersRequest.php / UpdateTeacherRequest.php
- AddHalaqaRequest.php / UpdateHalaqaRequest.php
- AddNotificationRequest.php / UpdateNotificationRequest.php
- AddPledgeRequest.php / UpdatePledgeRequest.php
- AddRecordPlanYearsRequest.php / UpdateRecordPlanYearsRequest.php
- AddSardRecordRequest.php / UpdateSardRecordRequest.php
- AddSerdScheduleRequest.php / UpdateSerdScheduleRequest.php
- AddScheduledSardStageRequest.php / UpdateScheduledSardStageRequest.php
- AddScheduledSardDayRequest.php / UpdateScheduledSardDayRequest.php
- AddScheduledExamRequest.php / UpdateScheduledExamRequest.php
- AddSardSessionRequest.php / UpdateSardSessionRequest.php
- AddTasshehRecordRequest.php
- AddRegionRequest.php / UpdateRegionRequest.php
- AddExamRecordRequest.php / UpdateExamRecordRequest.php
- AddEtqanRecordRequest.php / UpdateEtqanRecordRequest.php
- AddSardStageRequest.php
- AddAttendanceRequest.php / AddLateAttendanceRequest.php
- AttendanceByDateRequest.php / AttendanceByUserRequest.php
- StudentProfileRequest.php / TeacherProfileRequest.php / StudentRecordProfileRequest.php
- ReportRequest.php
- IdRequest.php

## مهم جداً

قواعد exists مثل:
exists:students,id
exists:teachers,id
exists:users,id

تحمي من إرسال IDs غير موجودة، لكنها ليست Authorization.
يعني وجود student_id صحيح لا يعني أن المعلم مسموح له بتعديل هذا الطالب.
Authorization و Object Ownership يجب أن تبقى في Middleware/Policy/Controller.

## مهم بخصوص مشروعك

بعض الجداول/الأعمدة في المشروع لها أسماء خاصة، مثل:
- barthdate
- tassheh_halaqa_id
- hesitiations_state
- Is_corrected
- record_sard_days
- serd_schedules

تم الحفاظ على الأسماء الموجودة في الكود المرسل قدر الإمكان.

قبل production راجع أسماء الجداول في migrations لأن بعض أسماء الجداول قد تختلف في مشروعك.
