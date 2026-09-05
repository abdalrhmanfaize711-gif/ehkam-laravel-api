<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddSerdScheduleRequest;
use App\Http\Requests\Api\IdRequest;
use App\Http\Requests\Api\UpdateSerdScheduleRequest;

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

    public function store(AddSerdScheduleRequest $request)
    {


        try {

            $schedule = DB::transaction(function () use ($request) {

                /*
                |--------------------------------------------------------------------------
                | STUDENT
                |--------------------------------------------------------------------------
                */

                $student = StudentModel::find(
                    $request->student_id
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
                    $request->student_id
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
                        $request->student_id,

                    'total_assigned_juz' =>
                        $request->total_assigned_juz,

                    'num_of_days' =>
                        $request->num_of_days,

                    'insert_date' =>
                        $request->insert_date,
                ];


                /*
                |--------------------------------------------------------------------------
                | ADD TEACHER IF PROVIDED
                |--------------------------------------------------------------------------
                */

                if (
                    isset($request->teacher_id) &&
                    $request->teacher_id !== null
                ) {

                    $scheduleData['teacher_id'] =
                        $request->teacher_id;
                }


                $schedule =
                    SerdSchedulesModel::create(
                        $scheduleData
                    );


                if (!$schedule || !$schedule->id) {

                    throw new \Exception(
                        'فشل في إنشاء خطة سرد.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE SARD DAYS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $request->days
                    as $index => $day
                ) {

                    $sardDay =
                        ScheduledSardDaysModel::create([

                            'student_id' =>
                                $request->student_id,

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
                                $request->insert_date,
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
                    'فشل في استرداد جداول سرد.',

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

    public function show(IdRequest $request)
    {
        try {


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
                    'تم استرجاع جدول سرد بنجاح.',

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
                    'فشل استرجاع جدول سرد.',

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

    public function update(UpdateSerdScheduleRequest $request)
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
                    'تم تحديث جدول سرد بنجاح.',

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
                    'فشل تحديث جدول سرد.',

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

    public function destroy(IdRequest $request)
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
                    'فشل حذف جدول سرد.',

                'error' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

            ], 500);
        }
    }
}