<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

use App\Models\StudentModel;
use App\Models\SerdSchedulesModel;
use App\Models\ScheduledSardDaysModel;

class SardScheduleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CREATE SARD SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        try {

            $validated = $request->validate([

                'student_id' => 'required|integer|exists:students,id',

                'teacher_id' => 'nullable|integer|exists:teachers,id',

                'total_assigned_juz' =>
                    'required|integer|min:1',

                'num_of_days' =>
                    'required|integer|min:1',

                'insert_date' =>
                    'required|date',

                'days' =>
                    'required|array|min:1',

                'days.*.sard_day' =>
                    'required|integer|min:1',

                'days.*.teacher_id' =>
                    'required|integer|exists:teachers,id',

                'days.*.num_of_sessions' =>
                    'required|integer|min:1',

                'days.*.from_surah' =>
                    'required|string',

                'days.*.from_ayah' =>
                    'required|integer|min:1',

                'days.*.to_surah' =>
                    'required|string',

                'days.*.to_ayah' =>
                    'required|integer|min:1',

                'days.*.time_assigned' =>
                    'required',

                'days.*.sard_date' =>
                    'required|date',
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق.',
                'errors' => $e->errors(),
            ], 422);
        }


        try {

            $schedule = DB::transaction(function () use ($validated) {

                /*
                |--------------------------------------------------------------------------
                | STUDENT
                |--------------------------------------------------------------------------
                */

                $student = StudentModel::find(
                    $validated['student_id']
                );

                if (!$student) {

                    throw new \Exception(
                        'لم يتم العثور على الطالب.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | PREVENT DUPLICATE SCHEDULE
                |--------------------------------------------------------------------------
                */

                $exists = SerdSchedulesModel::where(
                    'student_id',
                    $validated['student_id']
                )->exists();

                if ($exists) {

                    throw new \Exception(
                        'الخطة موجودة بالفعل لهذا الطالب.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE MAIN SCHEDULE
                |--------------------------------------------------------------------------
                */

                $scheduleData = [

                    'student_id' =>
                        $validated['student_id'],

                    'total_assigned_juz' =>
                        $validated['total_assigned_juz'],

                    'num_of_days' =>
                        $validated['num_of_days'],

                    'insert_date' =>
                        $validated['insert_date'],
                ];


                /*
                |--------------------------------------------------------------------------
                | ADD TEACHER IF PROVIDED
                |--------------------------------------------------------------------------
                */

                if (
                    isset($validated['teacher_id']) &&
                    $validated['teacher_id'] !== null
                ) {

                    $scheduleData['teacher_id'] =
                        $validated['teacher_id'];
                }


                $schedule =
                    SerdSchedulesModel::create(
                        $scheduleData
                    );


                if (!$schedule || !$schedule->id) {

                    throw new \Exception(
                        'فشل في إنشاء خطة sard.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE SARD DAYS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $validated['days']
                    as $index => $day
                ) {

                    $sardDay =
                        ScheduledSardDaysModel::create([

                            'student_id' =>
                                $validated['student_id'],

                            'serd_schedule_id' =>
                                $schedule->id,

                            'sard_day' =>
                                $day['sard_day'],

                            'teacher_id' =>
                                $day['teacher_id'],

                            'num_of_sessions' =>
                                $day['num_of_sessions'],

                            'from_surah' =>
                                $day['from_surah'],

                            'from_ayah' =>
                                $day['from_ayah'],

                            'to_surah' =>
                                $day['to_surah'],

                            'to_ayah' =>
                                $day['to_ayah'],

                            'time_assigned' =>
                                $day['time_assigned'],

                            'sard_date' =>
                                $day['sard_date'],

                            'insert_date' =>
                                $validated['insert_date'],
                        ]);


                    if (!$sardDay || !$sardDay->id) {

                        throw new \Exception(
                            "فشل في إنشاء يوم السرد في الفهرس {$index}."
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | RETURN SCHEDULE WITH DAYS
                |--------------------------------------------------------------------------
                */

                return $schedule->load('days');
            });


            return response()->json([

                'success' => true,

                'message' =>
                    'تم إنشاء خطة السرد أيام بنجاح.',

                'data' => $schedule,

            ], 201);


        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'فشل إنشاء جدول sard.',

                'error' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL SARD SCHEDULES
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        try {

            $schedules =
                SerdSchedulesModel::with([
                    'days'
                ])
                ->orderBy(
                    'insert_date',
                    'desc'
                )
                ->get();


            return response()->json([

                'success' => true,

                'message' =>
                    'تم استرجاع جداول سارد بنجاح..',

                'data' => $schedules,

            ], 200);


        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'فشل في استرداد جداول sard.',

                'error' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET ONE SARD SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function show(Request $request)
    {
        try {

            $request->validate([

                'id' =>
                    'required|integer|exists:serd_schedules,id',

            ]);


            $schedule =
                SerdSchedulesModel::with([
                    'days'
                ])->find(
                    $request->id
                );


            if (!$schedule) {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'لم يتم العثور على جدول مواعيد سرد.',

                ], 404);
            }


            return response()->json([

                'success' => true,

                'message' =>
                    'تم استرجاع جدول سارد بنجاح.',

                'data' => $schedule,

            ], 200);


        } catch (ValidationException $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'فشلت عملية التحقق.',

                'errors' =>
                    $e->errors(),

            ], 422);


        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'فشل استرجاع جدول sard.',

                'error' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE SARD SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request)
    {
        try {

            $validated = $request->validate([

                'id' =>
                    'required|integer|exists:serd_schedules,id',

                'teacher_id' =>
                    'nullable|integer|exists:teachers,id',

                'total_assigned_juz' =>
                    'sometimes|integer|min:1',

                'num_of_days' =>
                    'sometimes|integer|min:1',

                'insert_date' =>
                    'sometimes|date',

            ]);


            $schedule =
                SerdSchedulesModel::find(
                    $validated['id']
                );


            if (!$schedule) {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'لم يتم العثور على جدول مواعيد سرد.',

                ], 404);
            }


            $updateData = [];


            if (
                array_key_exists(
                    'teacher_id',
                    $validated
                )
            ) {

                $updateData['teacher_id'] =
                    $validated['teacher_id'];
            }


            if (
                array_key_exists(
                    'total_assigned_juz',
                    $validated
                )
            ) {

                $updateData['total_assigned_juz'] =
                    $validated['total_assigned_juz'];
            }


            if (
                array_key_exists(
                    'num_of_days',
                    $validated
                )
            ) {

                $updateData['num_of_days'] =
                    $validated['num_of_days'];
            }


            if (
                array_key_exists(
                    'insert_date',
                    $validated
                )
            ) {

                $updateData['insert_date'] =
                    $validated['insert_date'];
            }


            $schedule->update(
                $updateData
            );


            $schedule->load('days');


            return response()->json([

                'success' => true,

                'message' =>
                    'تم تحديث جدول سارد بنجاح.',

                'data' => $schedule,

            ], 200);


        } catch (ValidationException $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'فشلت عملية التحقق.',

                'errors' =>
                    $e->errors(),

            ], 422);


        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'فشل تحديث جدول sard.',

                'error' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE SARD SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request)
    {
        try {

            $request->validate([

                'id' =>
                    'required|integer|exists:serd_schedules,id',

            ]);


            $schedule =
                SerdSchedulesModel::find(
                    $request->id
                );


            if (!$schedule) {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'لم يتم العثور على جدول مواعيد سرد.',

                ], 404);
            }


            DB::transaction(function () use ($schedule) {

                /*
                |--------------------------------------------------------------------------
                | DELETE DAYS FIRST
                |--------------------------------------------------------------------------
                */

                ScheduledSardDaysModel::where(
                    'serd_schedule_id',
                    $schedule->id
                )->delete();


                /*
                |--------------------------------------------------------------------------
                | DELETE MAIN SCHEDULE
                |--------------------------------------------------------------------------
                */

                $schedule->delete();
            });


            return response()->json([

                'success' => true,

                'message' =>
                    'تم حذف جدول سرد بنجاح.',

            ], 200);


        } catch (ValidationException $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Validation failed.',

                'errors' =>
                    $e->errors(),

            ], 422);


        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'فشل حذف جدول sard.',

                'error' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

            ], 500);
        }
    }
}