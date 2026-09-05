<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SanctumTokenController extends Controller
{
    public function adminToken(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $admin = AdminModel::where('username', $request->username)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $admin->createToken('ehkam-admin', ['role:admin'])->plainTextToken;

        return response()->json([
            'message' => 'API token created successfully',
            'role' => 'admin',
            'token' => $token,
        ], 200);
    }

    public function teacherToken(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $teacher = TeacherModel::where('username', $request->username)->first();

        if (!$teacher || !Hash::check($request->password, $teacher->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $teacher->createToken('ehkam-teacher', ['role:teacher'])->plainTextToken;

        return response()->json([
            'message' => 'API token created successfully',
            'role' => 'teacher',
            'teacher_id' => $teacher->id,
            'token' => $token,
        ], 200);
    }

    public function studentToken(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'student_id' => ['required', 'integer'],
        ]);

        $user = User::where('name', $request->name)
            ->where('role', 'student')
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid student name or student ID',
            ], 401);
        }

        $student = StudentModel::where('user_id', $user->id)
            ->where('id', $request->student_id)
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Invalid student name or student ID',
            ], 401);
        }

        $token = $student->createToken('ehkam-student', ['role:student'])->plainTextToken;

        return response()->json([
            'message' => 'API token created successfully',
            'role' => 'student',
            'student_id' => $student->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'token' => $token,
        ], 200);
    }

    public function revokeToken(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'API token revoked successfully',
        ], 200);
    }
}
