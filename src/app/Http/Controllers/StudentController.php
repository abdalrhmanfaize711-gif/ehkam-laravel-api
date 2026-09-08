<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StudentRecordsByDateRequest;
use App\Http\Requests\Api\AddStudentsRequest;
use App\Http\Requests\Api\AddStudentRequest;
use App\Http\Requests\Api\DeleteStudentFromHalaqaRequest;
use App\Http\Requests\Api\UpdateStudentRequest;
use App\Http\Requests\Api\DeleteStudentRequest;
use App\Http\Requests\Api\UpdateStudentRecordRequest;
use App\Services\QuranPageService;
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
    public function delete_student(DeleteStudentRequest $request )
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

public function update_record_of_STD(
    UpdateStudentRecordRequest $request,
    QuranPageService $quranPageService
) {
    /*
    |--------------------------------------------------------------------------
    | 1. Basic Validation
    |--------------------------------------------------------------------------
    */

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | 2. Get Student
        |--------------------------------------------------------------------------
        */

        $student = StudentModel::find($request->student_id);

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
        | 6. Common Fields
        |--------------------------------------------------------------------------
        |
        | هذه الحقول مشتركة بين الإضافة والإتقان.
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


        /*
        |--------------------------------------------------------------------------
        | 7. Fields Specific To Addition
        |--------------------------------------------------------------------------
        |
        | num_of_pages لا يسمح للعميل بتعديله.
        | سيتم حسابه بواسطة QuranPageService.
        |
        */

        $additionOnlyFields = [
            'repeated_times',
            'daily_revision',
        ];


        /*
        |--------------------------------------------------------------------------
        | 8. Fields Specific To Etqan
        |--------------------------------------------------------------------------
        |
        | num_of_sheets لا يسمح للعميل بتعديله.
        | سيتم حسابه بواسطة QuranPageService.
        |
        */

        $etqanOnlyFields = [
            // لا يوجد حقول إضافية حالياً
        ];


        /*
        |--------------------------------------------------------------------------
        | 9. Update Addition Record
        |--------------------------------------------------------------------------
        */

        if ($recordType === 'addition') {

            /*
            |--------------------------------------------------------------------------
            | Reject Etqan-only fields
            |--------------------------------------------------------------------------
            |
            | العميل لا يستطيع إرسال num_of_sheets
            |
            */

            if ($request->has('num_of_sheets')) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'الحقل num_of_sheets غير مسموح به في سجل الإضافة',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Reject calculated field num_of_pages
            |--------------------------------------------------------------------------
            |
            | num_of_pages يتم حسابه من QuranPageService
            | ولا يجب أن يأتي من Flutter.
            |
            */

            if ($request->has('num_of_pages')) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'الحقل num_of_pages يتم حسابه تلقائياً ولا يمكن تعديله يدوياً',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Find Addition Record
            |--------------------------------------------------------------------------
            */

            $record = AdditionRecordsModel::where(
                'student_id',
                $student->id
            )->first();


            if (!$record) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على سجل الإضافة لهذا الطالب',
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | Prepare Update Data
            |--------------------------------------------------------------------------
            */

            $updateData = [];


            /*
            |--------------------------------------------------------------------------
            | Update Common Fields
            |--------------------------------------------------------------------------
            */

            foreach ($commonFields as $field) {

                if ($request->has($field)) {

                    $updateData[$field] = $request->input($field);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Update Addition-only Fields
            |--------------------------------------------------------------------------
            */

            foreach ($additionOnlyFields as $field) {

                if ($request->has($field)) {

                    $updateData[$field] = $request->input($field);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Recalculate Number Of Pages
            |--------------------------------------------------------------------------
            |
            | إذا قام المستخدم بتغيير أي جزء من نطاق الحفظ:
            |
            | from_surah
            | from_ayah
            | to_surah
            | to_ayah
            |
            | يجب إعادة حساب num_of_pages.
            |
            */

            $quranFieldChanged =
                $request->has('from_surah') ||
                $request->has('from_ayah') ||
                $request->has('to_surah') ||
                $request->has('to_ayah');


            if ($quranFieldChanged) {

                /*
                |--------------------------------------------------------------------------
                | Use new value if sent,
                | otherwise use existing database value
                |--------------------------------------------------------------------------
                */

                $fromSurah = $request->has('from_surah')
                    ? $request->input('from_surah')
                    : $record->from_surah;

                $fromAyah = $request->has('from_ayah')
                    ? $request->input('from_ayah')
                    : $record->from_ayah;

                $toSurah = $request->has('to_surah')
                    ? $request->input('to_surah')
                    : $record->to_surah;

                $toAyah = $request->has('to_ayah')
                    ? $request->input('to_ayah')
                    : $record->to_ayah;


                /*
                |--------------------------------------------------------------------------
                | Validate Quran Range
                |--------------------------------------------------------------------------
                */

                if (
                    $fromSurah === null ||
                    $fromAyah === null ||
                    $toSurah === null ||
                    $toAyah === null
                ) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'يجب تحديد بداية ونهاية الحفظ كاملة لحساب عدد الصفحات',
                    ], 422);
                }


                /*
                |--------------------------------------------------------------------------
                | Calculate Pages Using QuranPageService
                |--------------------------------------------------------------------------
                */

                $numOfPages = $quranPageService->calculatePages(
                    $fromSurah,
                    $fromAyah,
                    $toSurah,
                    $toAyah
                );


                /*
                |--------------------------------------------------------------------------
                | Save Calculated Pages
                |--------------------------------------------------------------------------
                */

                $updateData['num_of_pages'] = $numOfPages;
            }


            /*
            |--------------------------------------------------------------------------
            | Check If There Is Anything To Update
            |--------------------------------------------------------------------------
            */

            if (empty($updateData)) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إرسال أي بيانات لتحديث السجل',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Execute Update
            |--------------------------------------------------------------------------
            */

            $record->update($updateData);

            $message = 'تم تحديث سجل الإضافة بنجاح';
        }


        /*
        |--------------------------------------------------------------------------
        | 10. Update Etqan Record
        |--------------------------------------------------------------------------
        */

        elseif ($recordType === 'etqan') {

            /*
            |--------------------------------------------------------------------------
            | Reject Addition-only Fields
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Reject Calculated Fields
            |--------------------------------------------------------------------------
            |
            | num_of_sheets لا يأتي من Flutter.
            |
            */

            if ($request->has('num_of_sheets')) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'الحقل num_of_sheets يتم حسابه تلقائياً ولا يمكن تعديله يدوياً',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Reject num_of_pages
            |--------------------------------------------------------------------------
            |
            | الإتقان لا يحتاج أن يخزن num_of_pages.
            | يتم استخدامه داخلياً فقط لحساب num_of_sheets.
            |
            */

            if ($request->has('num_of_pages')) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'الحقل num_of_pages غير مسموح به في سجل الإتقان',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Find Etqan Record
            |--------------------------------------------------------------------------
            */

            $record = EtqanRecordModel::where(
                'student_id',
                $student->id
            )->first();


            if (!$record) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على سجل الإتقان لهذا الطالب',
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | Prepare Update Data
            |--------------------------------------------------------------------------
            */

            $updateData = [];


            /*
            |--------------------------------------------------------------------------
            | Update Common Fields
            |--------------------------------------------------------------------------
            */

            foreach ($commonFields as $field) {

                if ($request->has($field)) {

                    $updateData[$field] = $request->input($field);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Check Quran Fields Changed
            |--------------------------------------------------------------------------
            */

            $quranFieldChanged =
                $request->has('from_surah') ||
                $request->has('from_ayah') ||
                $request->has('to_surah') ||
                $request->has('to_ayah');


            /*
            |--------------------------------------------------------------------------
            | Recalculate Etqan Sheets
            |--------------------------------------------------------------------------
            */

            if ($quranFieldChanged) {

                /*
                |--------------------------------------------------------------------------
                | Get New Values Or Existing Values
                |--------------------------------------------------------------------------
                */

                $fromSurah = $request->has('from_surah')
                    ? $request->input('from_surah')
                    : $record->from_surah;

                $fromAyah = $request->has('from_ayah')
                    ? $request->input('from_ayah')
                    : $record->from_ayah;

                $toSurah = $request->has('to_surah')
                    ? $request->input('to_surah')
                    : $record->to_surah;

                $toAyah = $request->has('to_ayah')
                    ? $request->input('to_ayah')
                    : $record->to_ayah;


                /*
                |--------------------------------------------------------------------------
                | Validate Quran Range
                |--------------------------------------------------------------------------
                */

                if (
                    $fromSurah === null ||
                    $fromAyah === null ||
                    $toSurah === null ||
                    $toAyah === null
                ) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'يجب تحديد بداية ونهاية الحفظ كاملة لحساب عدد الأوراق',
                    ], 422);
                }


                /*
                |--------------------------------------------------------------------------
                | Calculate Number Of Pages
                |--------------------------------------------------------------------------
                */

                $numOfPages = $quranPageService->calculatePages(
                    $fromSurah,
                    $fromAyah,
                    $toSurah,
                    $toAyah
                );


                /*
                |--------------------------------------------------------------------------
                | Calculate Number Of Sheets
                |--------------------------------------------------------------------------
                */

                $numOfSheets = $quranPageService->calculateSheets(
                    $numOfPages
                );


                /*
                |--------------------------------------------------------------------------
                | Save Calculated Sheets
                |--------------------------------------------------------------------------
                */

                $updateData['num_of_sheets'] = $numOfSheets;
            }


            /*
            |--------------------------------------------------------------------------
            | Check If There Is Anything To Update
            |--------------------------------------------------------------------------
            */

            if (empty($updateData)) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إرسال أي بيانات لتحديث السجل',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Execute Update
            |--------------------------------------------------------------------------
            */

            $record->update($updateData);

            $message = 'تم تحديث سجل الإتقان بنجاح';
        }


        /*
        |--------------------------------------------------------------------------
        | 11. Commit Transaction
        |--------------------------------------------------------------------------
        */

        DB::commit();


        /*
        |--------------------------------------------------------------------------
        | 12. Return Response
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

