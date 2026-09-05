<?php

namespace App\Http\Middleware;

use App\Models\StudentModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admins and teachers are authorized by the route role middleware.
        if (!$user instanceof StudentModel) {
            return $next($request);
        }

        $student = $user;

        if ($request->has('student_id') && (int) $request->input('student_id') !== (int) $student->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($request->has('user_id') && (int) $request->input('user_id') !== (int) $student->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
