<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AddTeachersRequest;
use App\Http\Requests\Api\AddTeacherRequest;
use App\Http\Requests\Api\UpdateTeacherRequest;
use App\Http\Requests\Api\DeleteTeacherRequest;

use Illuminate\Http\Request;
use App\Models\TeacherModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
 public function add_teachers(AddTeachersRequest $request)
{
    DB::beginTransaction();

    try {

        // إذا كان الطلب Array مباشرة
        $teachersData = $request->has('teachers')
            ? $request->teachers
            : $request->all();

        // إذا أرسل عنصر واحد وليس Array
        if (isset($teachersData['name'])) {
            $teachersData = [$teachersData];
        }

        $result = [];

        foreach ($teachersData as $data) {
          
            $user = User::create([
                'name'       => $data['name'],
                'barthdate'  => $data['barthdate'],
                'join_date'  => $data['join_date'],
                 'region'  => $data['region'],
                  'role'  => 'teacher',
            ]);

            $teacher = TeacherModel::create([
                'user_id'  => $user->id,
                'username' => trim($data['username']),
                'password' => Hash::make($data['password']),
            ]);

            $result[] = [
                'user'    => $user,
                'teacher' => $teacher,
            ];
        }

        DB::commit();

        return response()->json([
            'message'  => 'تم إضافة المعلمين بنجاح',
            'teachers' => $result,
        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'message' => 'حدث خطأ أثناء إضافة المعلمين',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function add_teacher(AddTeacherRequest $request)
{
    DB::beginTransaction();

    try {

        $user = User::create([
            'name'      => $request->name,
            'barthdate' => $request->barthdate,
            'join_date' => $request->join_date,
            'region'    => $request->region,
            'role'      => 'teacher',
        ]);

        $teacher = TeacherModel::create([
            'user_id'  => $user->id,
            'username' => trim($request->username),
            'password' => Hash::make($request->password),
        ]);

        DB::commit();

        return response()->json([
            'message' => 'تم إضافة المعلم بنجاح',
            'teacher' => [
                'user'    => $user,
                'teacher' => $teacher,
            ],
        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'message' => 'حدث خطأ أثناء إضافة المعلم',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
    public function get_all_teachers()
    {
        $teachers = TeacherModel::with('user')->get();

        return response()->json([
            'message' => 'كل المعلمين',
            'teachers' => $teachers,
        ], 200);
    }

    public function update_teacher(UpdateTeacherRequest $request)
    {
        DB::beginTransaction();

        try {

            $user = User::find($request->user_id);

            if (!$user) {
                return response()->json([
                    'message' => 'لم يتم العثور على المعلم'
                ], 404);
            }

            $teacher = TeacherModel::where('user_id', $user->id)->first();

            if (!$teacher) {
                DB::rollBack();

                return response()->json([
                    'message' => 'لم يتم العثور على سجل المعلم'
                ], 404);
            }

            $user->update([
                'name'      => $request->name,
                'barthdate' => $request->barthdate,
                'region'    => $request->region,
            ]);

            $teacher->update([
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'canSurpriseTestStudents'=>$request->canSurpriseTestStudents
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم تحديث المعلم بنجاح',
                'teacher' => $teacher,
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete_teacher(DeleteTeacherRequest $request)
{
    DB::beginTransaction();

    try {

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على المعلم'
            ], 404);
        }

        $teacher = TeacherModel::where('user_id', $user->id)->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على المعلم'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Halaqats
        |--------------------------------------------------------------------------
        */

        $halaqats = DB::table('halaqats')
            ->where('teacher_id', $teacher->id)
            ->get();

        if ($halaqats->count() > 0) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المعلم لأنه مرتبط بحلقات. يجب نقل الحلقات إلى معلم آخر أولاً.',
                'halaqats_count' => $halaqats->count(),
                'halaqats' => $halaqats,
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Teacher
        |--------------------------------------------------------------------------
        */

        $teacher->delete();

        /*
        |--------------------------------------------------------------------------
        | Delete User
        |--------------------------------------------------------------------------
        */

        $user->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المعلم بنجاح'
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
}