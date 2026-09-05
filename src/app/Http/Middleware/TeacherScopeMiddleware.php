<?php

namespace App\Http\Middleware;

use App\Models\TeacherModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admins can inspect any teacher. Teachers may inspect only themselves.
        if (!$user instanceof TeacherModel) {
            return $next($request);
        }

        if ($request->has('teacher_id') && (int) $request->input('teacher_id') !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
