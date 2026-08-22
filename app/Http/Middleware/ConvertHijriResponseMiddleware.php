<?php

namespace App\Http\Middleware;

use Closure;
use MohamedSabil83\LaravelHijrian\Facades\Hijrian;

class ConvertHijriResponseMiddleware
{

    public function handle($request, Closure $next)
    {
        $response = $next($request);


        if (!$response->headers->contains(
            'Content-Type',
            'application/json'
        )) {
            return $response;
        }


        $data = $response->getData(true);


        $data = $this->convertDates($data);


        $response->setData($data);


        return $response;
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

                    $data[$key] = Hijrian::hijri($value)
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