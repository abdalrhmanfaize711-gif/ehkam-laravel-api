<?php

namespace App\Http\Middleware;

use App\Models\AdminModel;
use App\Models\TeacherModel;
use App\Models\StudentModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | تحديد Role المستخدم
        |--------------------------------------------------------------------------
        */

        $role = match (true) {

            $user instanceof AdminModel => 'admin',

            $user instanceof TeacherModel => 'teacher',

            $user instanceof StudentModel => 'student',

            default => null,
        };

        /*
        |--------------------------------------------------------------------------
        | التحقق من Role
        |--------------------------------------------------------------------------
        */

        if (!$role || !in_array($role, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | التحقق من صلاحية Sanctum Token
        |--------------------------------------------------------------------------
        */

        $token = $user->currentAccessToken();

        if ($token && !$token->can('role:' . $role)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}