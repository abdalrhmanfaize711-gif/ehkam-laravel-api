<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsTeacher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route('name');
          $Authintication = true;

     if($name == "AFK"){
        $Authintication =true;
        return $next($request);
     }
     else
        {
            $Authintication =false;
            return response('Unathorized', 403);
        }

    }
}
