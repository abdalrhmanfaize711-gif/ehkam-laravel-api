<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminModel;
use App\Models\TeacherModel;
use App\Models\HalaqatModel;
use App\Models\StudentModel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
  public function login(Request $request)
{
    // البحث عن المدير
    $admin = AdminModel::where('username', $request->username)->first();

    if ($admin) {

        if (!Hash::check($request->password, $admin->password)) {
            return response()->json([
                'message' => 'كلمة مرور خاطئة'
            ], 401);
        }

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'role'    => 'admin',
            'info'    => [
                'ID' => $admin->id,
            ]
        ], 200);
    }

    // البحث عن المعلم
    $teacher = TeacherModel::where('username', $request->username)->first();

    if ($teacher) {

        if (!Hash::check($request->password, $teacher->password)) {
            return response()->json([
                'message' => 'كلمة مرور خاطئة'
            ], 401);
        }

        $info = User::find($teacher->user_id);

        $teacherInfo = HalaqatModel::where('teacher_id', $teacher->id)->first();

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'role'    => 'teacher',
            'info'    => [
                'name'   => $info?->name,
                'ID'     => $teacher->id,
            ]
        ], 200);
    }

    // لم يتم العثور على أي مستخدم
    return response()->json([
        'message' => 'اسم المستخدم غير موجود'
    ], 404);
}

public function loginStudent(Request $request)
{

    $user = User::where('name', $request->name)
                ->where('role', 'student')
                ->first();

    if (!$user) {
        return response()->json([
            'message' => 'لم يتم العثور على الطالب'
        ], 404);
    }

    $student = StudentModel::where('user_id', $user->id)
                           ->where('id', $request->id)
                           ->first();

    if (!$student) {
        return response()->json([
            'message' => 'هوية طالب غير صالحة'
        ], 401);
    }

    return response()->json([
        'message' => 'تم تسجيل الدخول بنجاح',
        'role' => 'student',
        'info' => [
            'student_id' => $student->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'stage' => $student->stage,
            'halaqa_id' => $student->halaqa_id,
            'tassheh_halaqa_id' => $student->tassheh_halaqa_id,
        ]
    ], 200);
}

}
