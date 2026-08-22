<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MohamedSabil83\LaravelHijrian\Facades\Hijrian;

class ConvertHijriRequestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $data = $this->convertDates($request->all());

        $request->replace($data);

        return $next($request);
    }


    private function convertDates($data)
    {
        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $data[$key] = $this->convertDates($value);
                continue;
            }


            if ($this->isDateField($key) && !empty($value)) {

                try {

                    $data[$key] = Hijrian::gregory($value)
                        ->format('Y-m-d');

                } catch (\Throwable $e) {

                }
            }
        }

        return $data;
    }


    private function isDateField($key)
    {
        return str_ends_with($key, '_date')
            || str_contains($key, 'date');
    }
}