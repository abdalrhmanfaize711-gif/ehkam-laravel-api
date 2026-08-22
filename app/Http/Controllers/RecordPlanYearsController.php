<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RecordPlanYearsModel;
use App\Models\AdditionRecordsModel;
use App\Models\NotificationsModel;
use App\Models\StudentModel;
class RecordPlanYearsController extends Controller
{

private function checkYearPlan($studentId)
{
    // جلب الخطة الحالية للطالب
    $plan = RecordPlanYearsModel::where('student_id', $studentId)
        ->latest('id')
        ->first();

    if (!$plan) {
        return;
    }

    // آخر سجل حفظ
    $lastRecord = AdditionRecordsModel::where('student_id', $studentId)
        ->latest('addition_date')
        ->first();

    if (!$lastRecord) {
        return;
    }

    // تاريخ بداية الخطة
    $startDate = Carbon::parse($plan->start_date);

    // تاريخ نهاية الخطة
    $endDate = $startDate->copy()->addYear();

    // اليوم الحالي
    $today = Carbon::today();

    /*
        نفحص فقط كل عشرة أيام
    */

    $days = $startDate->diffInDays($today);

    if ($days % 10 != 0) {
        return;
    }

    /*
        إذا لم تنته السنة فلا يوجد فحص
    */

    if ($today->lt($endDate)) {
        return;
    }

    /*
        هل حقق الحد الأدنى؟
    */

    $completed =

        $lastRecord->to_surah == $plan->min_to_surah

        &&

        $lastRecord->to_ayah >= $plan->min_to_ayah;

    if ($completed) {

        return;

    }

    /*
        إنشاء إشعار مرة واحدة فقط
    */

    $exists = NotificationsModel::where('student_id', $studentId)
        ->where('title', 'إشعار عدم إكمال الخطة السنوية')
        ->exists();

    if ($exists) {
        return;
    }

    $halaqaId = StudentModel::where('id', $studentId)
        ->value('halaqa_id');

    NotificationsModel::create([

        'student_id' => $studentId,

        'halaqa_id' => $halaqaId,

        'title' => 'إشعار عدم إكمال الخطة السنوية',

        'notification_time' => now(),

        'insert_date' => now()->toDateString()

    ]);
}

    public function IsCompleated(Request $request)
{
    // أول سجل إضافة للطالب (بداية الحفظ)
    $firstRecord = RecordPlanYearsModel::where('student_id', $request->student_id)
        ->oldest('addition_date')
        ->first();

    // آخر سجل إضافة للطالب (آخر ما حفظه)
    $lastRecord = AdditionRecordsModel::where('student_id', $request->student_id)
        ->latest('addition_date')
        ->first();


    if (!$firstRecord || !$lastRecord) {
        return response()->json([
            'message' => 'السجل غير موجود'
        ], 404);
    }


    $isCompleted =($firstRecord->min_to_surah != $lastRecord->to_surah);



    if ($isCompleted) {


        // منع تكرار الإشعار
        $exists = NotificationsModel::where('student_id', $request->student_id)
            ->where('title', 'إشعار لم يكمل الحد الأدنى')
            ->exists();


        if ($exists) {

            return response()->json([
                'message' => 'الإشعار موجود بالفعل'
            ], 200);

        }


        $notification = NotificationsModel::create([

            'student_id' => $request->student_id,

            'halaqa_id' => $request->halaqa_id,

            'title' => 'إشعار لم يكمل الحد الأدنى',

            'notification_time' => now()->format('H:i:s'),

            'insert_date' => now()->toDateString(),

        ]);


        return response()->json([

            'message' => 'تم إنشاء إشعار إكمال المرحلة بنجاح',

            'notification' => $notification

        ], 201);

    }


    return response()->json([

        'message' => 'لم يتم إكمال المرحلة بعد',

        'start_from' => $firstRecord->from_surah,

        'current_to' => $lastRecord->to_surah

    ], 200);

}
    public function add_record_plan_years(Request $request)
    {
        DB::beginTransaction();

        try {

            $record = RecordPlanYearsModel::create([
                'student_id' => $request->student_id,
                'start_date' => $request->start_date,
                'min_juz_target' => $request->min_juz_target,
                'min_from_surah' => $request->min_from_surah,
                'min_from_ayah' => $request->min_from_ayah,
                'min_to_surah' => $request->min_to_surah,
                'min_to_ayah' => $request->min_to_ayah,
                'ideal_juz_target' => $request->ideal_juz_target,
                'ideal_from_surah' => $request->ideal_from_surah,
                'ideal_from_ayah' => $request->ideal_from_ayah,
                'ideal_to_surah' => $request->ideal_to_surah,
                'ideal_to_ayah' => $request->ideal_to_ayah,
                'insert_date' => $request->insert_date,
            ]);
          
            DB::commit();

            return response()->json([
                'message' => 'تم إضافة الخطة بنجاح',
                'record_plan_years' => $record,
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_record_plan_years()
    {
        $records = RecordPlanYearsModel::all();

        return response()->json([
            'message' => 'تم استرجاع الخطة بنجاح',
            'record_plan_years' => $records,
        ], 200);
    }

    public function get_special_record_plan_years(Request $request)
    {
        $record = RecordPlanYearsModel::find($request->id);

        if (!$record) {
            return response()->json([
                'message' => 'لم يتم العثور على الخطة',
            ], 404);
        }

        return response()->json([
            'message' => 'تم استرجاع الخطة بنجاح',
            'record_plan_years' => $record,
        ], 200);
    }

    public function update_record_plan_years(Request $request)
    {
        DB::beginTransaction();

        try {

            $record = RecordPlanYearsModel::find($request->id);

            if (!$record) {
                return response()->json([
                    'message' => 'لم يتم العثور على الخطة',
                ], 404);
            }

            $record->update([
                'student_id' => $request->student_id,
                'start_date' => $request->start_date,
                'min_juz_target' => $request->min_juz_target,
                'min_from_surah' => $request->min_from_surah,
                'min_from_ayah' => $request->min_from_ayah,
                'min_to_surah' => $request->min_to_surah,
                'min_to_ayah' => $request->min_to_ayah,
                'ideal_juz_target' => $request->ideal_juz_target,
                'ideal_from_surah' => $request->ideal_from_surah,
                'ideal_from_ayah' => $request->ideal_from_ayah,
                'ideal_to_surah' => $request->ideal_to_surah,
                'ideal_to_ayah' => $request->ideal_to_ayah,
                'insert_date' => $request->insert_date,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم تحديث الخطة بنجاح',
                'record_plan_years' => $record,
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete_record_plan_years(Request $request)
    {
        DB::beginTransaction();

        try {

            $record = RecordPlanYearsModel::find($request->id);

            if (!$record) {
                return response()->json([
                    'message' => 'لم يتم العثور على الخطة',
                ], 404);
            }

            $record->delete();

            DB::commit();

            return response()->json([
                'message' => 'تم حذف الخطة بنجاح',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}