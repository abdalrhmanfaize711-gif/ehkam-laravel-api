<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
 public function add_teachers(Request $request)
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
public function add_teacher(Request $request)
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

    public function update_teacher(Request $request)
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

    public function delete_teacher(Request $request)
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

            if ($teacher) {
                $teacher->delete();
            }

            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'تم حذف المعلم بنجاح'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}