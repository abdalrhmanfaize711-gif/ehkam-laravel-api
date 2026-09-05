<?php

namespace App\Http\Middleware;

use App\Models\AdminModel;
use App\Models\TeacherModel;
use App\Models\StudentModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        $user = $request->user();

        // لا يوجد مستخدم مصادق عليه
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | تحديد Role
        |--------------------------------------------------------------------------
        */

        if ($user instanceof AdminModel) {

            $role = 'admin';

        } elseif ($user instanceof TeacherModel) {

            $role = 'teacher';

        } elseif ($user instanceof StudentModel) {

            $role = 'student';

        } else {

            return response()->json([
                'message' => 'نوع المستخدم غير معروف',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | التحقق من Role المطلوب للـ Route
        |--------------------------------------------------------------------------
        */

        if (!in_array($role, $roles, true)) {

            return response()->json([
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المورد',
                'role' => $role,
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | التحقق من Sanctum Ability
        |--------------------------------------------------------------------------
        */

        $token = $user->currentAccessToken();

        if ($token && !$token->can('role:' . $role)) {

            return response()->json([
                'message' => 'ليس لديك صلاحية باستخدام هذا الـ Token',
            ], 403);
        }

        return $next($request);
    }
}