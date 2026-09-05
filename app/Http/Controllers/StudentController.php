<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StudentRecordsByDateRequest;
use App\Http\Requests\Api\AddStudentsRequest;
use App\Http\Requests\Api\AddStudentRequest;
use App\Http\Requests\Api\DeleteStudentFromHalaqaRequest;
use App\Http\Requests\Api\UpdateStudentRequest;
use App\Http\Requests\Api\DeleteStudentRequest;
use App\Http\Requests\Api\UpdateStudentRecordRequest;

use App\Models\StudentModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AdditionRecordsModel;
use App\Models\EtqanRecordModel;
use App\Models\NotsModel;
use Illuminate\Validation\Rule;


class StudentController extends Controller
{
   public function getDatesRecords()
{
    $dates = DB::table('addition_records')
        ->select('addition_date')
        ->distinct()
        ->orderByDesc('addition_date')
        ->limit(6)
        ->pluck('addition_date');

    return response()->json([
        'success' => true,
        'dates' => $dates
    ], 200);
}
public function getStudentRecordsByDate(StudentRecordsByDateRequest $request)
{
    $validated = $request->validate([
        'student_id' => [
            'required',
            'integer',
            'exists:students,id',
        ],

        'date' => [
            'required',
            'date',
        ],
    ]);

    $studentId = $validated['student_id'];
    $date = $validated['date'];

    /*
    |--------------------------------------------------------------------------
    | Addition Records
    |--------------------------------------------------------------------------
    */

    $additionRecords = DB::table('addition_records')
        ->where('student_id', $studentId)
        ->whereDate('addition_date', $date)
        ->get()
        ->map(function ($record) {

            $record->record_type = 'addition';

            return $record;
        });


    /*
    |--------------------------------------------------------------------------
    | Etqan Records
    |--------------------------------------------------------------------------
    */

    $etqanRecords = DB::table('etqan_record')
        ->where('student_id', $studentId)
        ->whereDate('addition_date', $date)
        ->get()
        ->map(function ($record) {

            $record->record_type = 'etqan';

            return $record;
        });


    /*
    |--------------------------------------------------------------------------
    | Merge Records
    |--------------------------------------------------------------------------
    */

    $records = $additionRecords
        ->concat($etqanRecords)
        ->sortBy('addition_date')
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return response()->json([
        'success' => true,

        'message' => $records->isEmpty()
            ? 'لا توجد سجلات لهذا الطالب في هذا التاريخ'
            : 'تم جلب سجلات الطالب بنجاح',

        'student_id' => $studentId,

        'date' => $date,

        'count' => $records->count(),

        'records' => $records,

    ], 200);
}
    /*
    |--------------------------------------------------------------------------
    | إضافة طالب
    |--------------------------------------------------------------------------
    */

  public function add_students(AddStudentsRequest $request)
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
    
public function add_student(AddStudentRequest $request)
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

      
public function Delete_STD_from_halaqa(DeleteStudentFromHalaqaRequest $request)
{
    try {

        $student = StudentModel::findOrFail($request->student_id);

        $student->update([
            'halaqa_id' => null,
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
   public function update_student(UpdateStudentRequest $request)
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
    public function delete_student(DeleteStudentRequest $request)
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
            
            $student->halaqa_id = null;
            $student->tassheh_halaqa_id = null;
            $student->region = null;
            $student->save();

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

public function update_record_of_STD(UpdateStudentRecordRequest $request)
{
    /*
    |--------------------------------------------------------------------------
    | 1. Basic Validation
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([
        'id' => [
            'required',
            'integer',
        ],

        'student_id' => [
            'required',
            'integer',
            'exists:students,id',
        ],

        'from_surah' => [
            'sometimes',
            'nullable',
            'string',
        ],

        'from_ayah' => [
            'sometimes',
            'nullable',
            'integer',
            'min:1',
        ],

        'to_surah' => [
            'sometimes',
            'nullable',
            'string',
        ],

        'to_ayah' => [
            'sometimes',
            'nullable',
            'integer',
            'min:1',
        ],


        'repeated_times' => [
            'sometimes',
            'nullable',
            'integer',
            'min:0',
        ],

        'memorization_state' => [
            'sometimes',
            'nullable',
            'string',
        ],

        'addition_date' => [
            'sometimes',
            'nullable',
            'date',
        ],

        'general_revision' => [
            'sometimes',
            'nullable',
            'boolean',
        ],

        'daily_revision' => [
            'sometimes',
            'nullable',
            'boolean',
        ],
    ]);


    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | 2. Get Student
        |--------------------------------------------------------------------------
        */

        $student = StudentModel::find($validated['student_id']);

        if (!$student) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الطالب',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Verify User Role
        |--------------------------------------------------------------------------
        */

        $role = User::where('id', $student->user_id)
            ->value('role');

        if ($role !== 'student') {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'المستخدم المرتبط ليس طالباً',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Get Student Stage
        |--------------------------------------------------------------------------
        */

        $stage = trim($student->stage);


        /*
        |--------------------------------------------------------------------------
        | 5. Determine Record Type
        |--------------------------------------------------------------------------
        */

        $etqanStages = [
            'إتقان أول',
            'إتقان ثاني',
            'إتقان ثالث',
        ];

        $additionStages = [
            'إضافة',
        ];


        if (in_array($stage, $etqanStages, true)) {

            $recordType = 'etqan';

        } elseif (in_array($stage, $additionStages, true)) {

            $recordType = 'addition';

        } else {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'مرحلة الطالب غير معروفة',
                'stage' => $stage,
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Prepare Only Fields That Were Actually Sent
        |--------------------------------------------------------------------------
        |
        | مهم:
        | لا نستخدم ?? null هنا.
        |
        | إذا لم يرسل العميل الحقل، لن يتم تعديله.
        |
        */

        $commonFields = [
            'from_surah',
            'from_ayah',
            'to_surah',
            'to_ayah',
            'memorization_state',
            'addition_date',
            'general_revision',
        ];


        $additionOnlyFields = [
            'num_of_pages',
            'repeated_times',
            'daily_revision',
        ];


        $etqanOnlyFields = [
            'num_of_sheets',
        ];


        /*
        |--------------------------------------------------------------------------
        | 7. Update Addition Record
        |--------------------------------------------------------------------------
        */

        if ($recordType === 'addition') {

            /*
            | إذا أرسل العميل حقول خاصة بالإتقان
            | نرفض الطلب.
            */

            foreach ($etqanOnlyFields as $field) {

                if ($request->has($field)) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => "الحقل {$field} غير مسموح به في سجل الإضافة",
                    ], 422);
                }
            }


            /*
            | البحث عن السجل مع student_id
            */

            $record = AdditionRecordsModel::where('id', $validated['id'])
                ->where('student_id', $student->id)
                ->first();


            if (!$record) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على سجل الإضافة لهذا الطالب',
                ], 404);
            }


            /*
            | تجهيز البيانات التي سيتم تحديثها
            */

            $updateData = [];

            foreach ($commonFields as $field) {

                if ($request->has($field)) {

                    $updateData[$field] = $validated[$field];
                }
            }


            foreach ($additionOnlyFields as $field) {

                if ($request->has($field)) {

                    $updateData[$field] = $validated[$field];
                }
            }


            /*
            | لا يوجد شيء لتحديثه
            */

            if (empty($updateData)) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إرسال أي بيانات لتحديث السجل',
                ], 422);
            }


            /*
            | تنفيذ التحديث
            */

            $record->update($updateData);

            $message = 'تم تحديث سجل الإضافة بنجاح';
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Update Etqan Record
        |--------------------------------------------------------------------------
        */

        elseif ($recordType === 'etqan') {

            /*
            | إذا أرسل العميل حقول خاصة بالإضافة
            | نرفض الطلب.
            */

            foreach ($additionOnlyFields as $field) {

                if ($request->has($field)) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => "الحقل {$field} غير مسموح به في سجل الإتقان",
                    ], 422);
                }
            }


            /*
            | البحث عن السجل مع student_id
            */

            $record = EtqanRecordModel::where('id', $validated['id'])
                ->where('student_id', $student->id)
                ->first();


            if (!$record) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على سجل الإتقان لهذا الطالب',
                ], 404);
            }


            /*
            | تجهيز البيانات
            */

            $updateData = [];


            foreach ($commonFields as $field) {

                if ($request->has($field)) {

                    $updateData[$field] = $validated[$field];
                }
            }


            foreach ($etqanOnlyFields as $field) {

                if ($request->has($field)) {

                    $updateData[$field] = $validated[$field];
                }
            }


            /*
            | لا يوجد شيء للتحديث
            */

            if (empty($updateData)) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إرسال أي بيانات لتحديث السجل',
                ], 422);
            }


            /*
            | تنفيذ التحديث
            */

            $record->update($updateData);

            $message = 'تم تحديث سجل الإتقان بنجاح';
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Commit Transaction
        |--------------------------------------------------------------------------
        */

        DB::commit();


        /*
        |--------------------------------------------------------------------------
        | 10. Return Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => $message,
            'record_type' => $recordType,
            'stage' => $stage,
            'student_id' => $student->id,
            'record' => $record->fresh(),
        ], 200);


    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث السجل',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}

