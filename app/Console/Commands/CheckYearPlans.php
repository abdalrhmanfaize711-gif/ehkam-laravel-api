<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

use App\Models\RecordPlanYearsModel;
use App\Models\AdditionRecordsModel;
use App\Models\NotificationsModel;
use App\Models\StudentModel;

class CheckYearPlans extends Command
{
    protected $signature = 'students:check-year-plans';

    protected $description = 'Check all students who have year plans';

    public function handle()
    {
        /*
        |--------------------------------------------------------------------------
        | جلب جميع الطلاب الذين لديهم خطة
        |--------------------------------------------------------------------------
        */

        $plans = RecordPlanYearsModel::query()
            ->select('student_id')
            ->whereNotNull('student_id')
            ->distinct()
            ->get();

        $checked = 0;
        $notifications = 0;

        /*
        |--------------------------------------------------------------------------
        | فحص كل طالب
        |--------------------------------------------------------------------------
        */

        foreach ($plans as $plan) {

            $result = $this->checkYearPlan($plan->student_id);

            $checked++;

            if ($result === true) {
                $notifications++;
            }
        }

        $this->info(
            "تم فحص {$checked} طالب، وتم إنشاء {$notifications} إشعار."
        );

        return Command::SUCCESS;
    }


    private function checkYearPlan($studentId)
    {
        /*
        |--------------------------------------------------------------------------
        | جلب آخر خطة للطالب
        |--------------------------------------------------------------------------
        */

        $plan = RecordPlanYearsModel::where(
            'student_id',
            $studentId
        )
        ->latest('id')
        ->first();

        if (!$plan) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | تاريخ بداية الخطة
        |--------------------------------------------------------------------------
        */

        $startDate = Carbon::parse(
            $plan->start_date
        )->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | تاريخ اليوم
        |--------------------------------------------------------------------------
        */

        $today = Carbon::today();


        /*
        |--------------------------------------------------------------------------
        | إذا الخطة لم تبدأ بعد
        |--------------------------------------------------------------------------
        */

        if ($today->lt($startDate)) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | عدد الأشهر التي مرت من بداية الخطة
        |--------------------------------------------------------------------------
        */

        $monthsPassed = $startDate->diffInMonths($today);


        /*
        |--------------------------------------------------------------------------
        | تاريخ الفحص الشهري
        |--------------------------------------------------------------------------
        */

        $checkDate = $startDate
            ->copy()
            ->addMonths($monthsPassed)
            ->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | إذا لم يكن اليوم موعد الفحص الشهري
        |--------------------------------------------------------------------------
        */

        if (!$today->equalTo($checkDate)) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | جلب آخر سجل إضافة للطالب
        |--------------------------------------------------------------------------
        */

        $lastRecord = AdditionRecordsModel::where(
            'student_id',
            $studentId
        )
        ->latest('addition_date')
        ->first();


        /*
        |--------------------------------------------------------------------------
        | لا يوجد سجل إضافة
        |--------------------------------------------------------------------------
        */

        if (!$lastRecord) {

            $this->createYearPlanNotification(
                $studentId
            );

            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | التحقق من تحقيق الخطة
        |--------------------------------------------------------------------------
        */

        $completed =
            $lastRecord->to_surah == $plan->min_to_surah
            &&
            $lastRecord->to_ayah >= $plan->min_to_ayah;


        /*
        |--------------------------------------------------------------------------
        | الطالب أكمل المطلوب
        |--------------------------------------------------------------------------
        */

        if ($completed) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | الطالب لم يكمل المطلوب
        |--------------------------------------------------------------------------
        */

        $this->createYearPlanNotification(
            $studentId
        );

        return true;
    }


    private function createYearPlanNotification($studentId)
    {
        /*
        |--------------------------------------------------------------------------
        | منع تكرار إشعار نفس اليوم
        |--------------------------------------------------------------------------
        */

        $exists = NotificationsModel::where(
            'student_id',
            $studentId
        )
        ->where(
            'title',
            'إشعار عدم إكمال الخطة السنوية'
        )
        ->whereDate(
            'insert_date',
            Carbon::today()
        )
        ->exists();


        if ($exists) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | جلب الحلقة
        |--------------------------------------------------------------------------
        */

        $halaqaId = StudentModel::where(
            'id',
            $studentId
        )->value('halaqa_id');


        /*
        |--------------------------------------------------------------------------
        | إنشاء الإشعار
        |--------------------------------------------------------------------------
        */

        NotificationsModel::create([

            'student_id' => $studentId,

            'halaqa_id' => $halaqaId,

            'title' => 'إشعار عدم إكمال الخطة السنوية',

            'notification_time' => now()->format('H:i:s'),

            'insert_date' => Carbon::today()->toDateString(),

        ]);
    }
}