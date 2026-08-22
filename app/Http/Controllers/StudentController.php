<?php

namespace App\Http\Controllers;

use App\Models\StudentModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AdditionRecordsModel;
use App\Models\EtqanRecordModel;

class StudentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | إضافة طالب
    |--------------------------------------------------------------------------
    */
  public function add_students(Request $request)
{
    DB::beginTransaction();

    try {

        // Accept one student or multiple students
        $students = $request->has('students')
            ? $request->students
            : [$request->all()];

        $result = [];

        foreach ($students as $data) {

            $user = User::create([
                'name'       => $data['name'],
                'barthdate'  => $data['barthdate'],
                'region'     => $data['region'],
                'join_date'  => $data['join_date'],
                'role'       => 'student',
            ]);

            $student = StudentModel::create([
                'user_id'           => $user->id,
                'halaqa_id'         => $data['halaqa_id'],
                'tassheh_halaqa_id' => $data['tassheh_halaqa_id'] ?? null,
                'stage'             => $data['stage'],
            ]);

            $result[] = $student;
        }

        DB::commit();

        return response()->json([
            'message'  => 'تم إضافة الطالب/الطلاب بنجاح',
            'students' => $result,
        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'message' => $e->getMessage(),
        ], 500);
    }
}
    
public function add_student(Request $request)
{
    DB::beginTransaction();

    try {

        $user = User::create([
            'name'      => $request->name,
            'barthdate' => $request->barthdate,
            'region'    => $request->region,
            'join_date' => $request->join_date,
            'role'      => 'student',
        ]);

        $student = StudentModel::create([
            'user_id'           => $user->id,
            'halaqa_id'         => $request->halaqa_id,
            'tassheh_halaqa_id' => $request->tassheh_halaqa_id,
            'stage'             => $request->stage,
        ]);

        DB::commit();  

        return response()->json([
            'message' => 'تم إضافة الطالب بنجاح',
            'student' => $student,
        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'message' => $e->getMessage(),
        ], 500);
    }
}

    /*
    |--------------------------------------------------------------------------
    | جلب جميع الطلاب
    |--------------------------------------------------------------------------
    */
   public function get_all_student()
{
    $students = StudentModel::with('user')
        ->get()
        ->each(function ($student) {
            $student->halaqa_id ??= 0;
        });

    return response()->json([
        'message'  => 'جميع الطلاب',
        'students' => $students,
    ], 200);
}

    /*
    |--------------------------------------------------------------------------
    | حذف سجلات المرحلة القديمة
    |--------------------------------------------------------------------------
    |
    | إذا كانت المرحلة القديمة:
    |
    | إضافة
    |     => حذف addition_records
    |
    | إتقان أول / ثاني / ثالث
    |     => حذف etqan_record
    |
    |--------------------------------------------------------------------------
    */
    private function clearStudentStageRecords($studentId, $oldStage)
    {
        /*
        |--------------------------------------------------------------------------
        | إذا كانت المرحلة القديمة إضافة
        |--------------------------------------------------------------------------
        */
        if ($oldStage === 'إضافة') {

            AdditionRecordsModel::where(
                'student_id',
                $studentId
            )->delete();

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | إذا كانت المرحلة القديمة إحدى مراحل الإتقان
        |--------------------------------------------------------------------------
        */
        if (in_array($oldStage, [
            'إتقان أول',
            'إتقان ثاني',
            'إتقان ثالث',
        ], true)) {

            EtqanRecordModel::where(
                'student_id',
                $studentId
            )->delete();

            return;
        }
    }

      
public function Delete_STD_from_halaqa(Request $request)
{
    try {

        $student = StudentModel::findOrFail($request->student_id);

        $student->update([
            'halaqa_id' => 0,
        ]);

        return response()->json([
            'message' => 'تم حذف الطالب من الحلقة بنجاح',
            'student' => $student,
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'message' => $e->getMessage(),
        ], 500);
    }
}

    /*
    |--------------------------------------------------------------------------
    | تعديل بيانات الطالب
    |--------------------------------------------------------------------------
    */
   public function update_student(Request $request)
{
    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | تحديد البيانات المرسلة
        |--------------------------------------------------------------------------
        |
        | يدعم:
        |
        | 1- طالب واحد
        | 2- عدة طلاب
        |
        */

        if ($request->has('students')) {

            $students = $request->students;

        } else {

            $students = [$request->all()];
        }

        $result = [];

        foreach ($students as $data) {

            $user = User::findOrFail($data['user_id']);

            $student = StudentModel::where(
                'user_id',
                $user->id
            )->firstOrFail();

            $oldStage = $student->stage;
            $newStage = $data['stage'];

            /*
            |--------------------------------------------------------------------------
            | حذف بيانات المرحلة السابقة عند تغيير المرحلة
            |--------------------------------------------------------------------------
            */

            if ($oldStage !== $newStage) {

                $this->clearStudentStageRecords(
                    $student->id,
                    $oldStage
                );
            }

            /*
            |--------------------------------------------------------------------------
            | تحديث المستخدم
            |--------------------------------------------------------------------------
            */

            $user->update([
                'name'      => $data['name'],
                'barthdate' => $data['barthdate'],
                'region'    => $data['region'],
                'join_date' => $data['join_date'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | تحديث الطالب
            |--------------------------------------------------------------------------
            */

            $student->update([
                'halaqa_id'         => $data['halaqa_id'],
                'tassheh_halaqa_id' => $data['tassheh_halaqa_id'] ,
                'stage'             => $newStage,
            ]);

            $result[] = [
                'user_id'        => $user->id,
                'student_id'     => $student->id,
                'old_stage'      => $oldStage,
                'new_stage'      => $newStage,
                'stage_changed'  => $oldStage !== $newStage,
            ];
        }

        DB::commit();

        return response()->json([
            'message' => 'تم تعديل الطلاب بنجاح',
            'count'   => count($result),
            'students'=> $result,
        ], 200);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'message' => $e->getMessage(),
        ], 500);
    }
}
    /*
    |--------------------------------------------------------------------------
    | حذف الطالب
    |--------------------------------------------------------------------------
    */
    public function delete_student(Request $request)
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | البحث عن المستخدم
            |--------------------------------------------------------------------------
            */
            $user = User::find($request->user_id);

            if (!$user) {

                DB::rollBack();

                return response()->json([
                    'message' => 'الطالب غير موجود',
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | البحث عن الطالب
            |--------------------------------------------------------------------------
            */
            $student = StudentModel::where(
                'user_id',
                $user->id
            )->first();


            /*
            |--------------------------------------------------------------------------
            | حذف الطالب
            |--------------------------------------------------------------------------
            */
            if ($student) {
                $student->delete();
            }


            /*
            |--------------------------------------------------------------------------
            | حذف المستخدم
            |--------------------------------------------------------------------------
            */
            $user->delete();


            /*
            |--------------------------------------------------------------------------
            | تأكيد العملية
            |--------------------------------------------------------------------------
            */
            DB::commit();


            return response()->json([
                'message' => 'تم حذف الطالب بنجاح',
            ], 200);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

