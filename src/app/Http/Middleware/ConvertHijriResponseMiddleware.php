<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use MohamedSabil83\LaravelHijrian\Facades\Hijrian;

class ConvertHijriResponseMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        /*
        |--------------------------------------------------------------------------
        | JSON Response فقط
        |--------------------------------------------------------------------------
        */

        if (!$response->headers->contains(
            'Content-Type',
            'application/json'
        )) {
            return $response;
        }

        /*
        |--------------------------------------------------------------------------
        | الحصول على بيانات Response
        |--------------------------------------------------------------------------
        */

        $data = $response->getData(true);

        /*
        |--------------------------------------------------------------------------
        | تحويل التواريخ من ميلادي إلى هجري
        |--------------------------------------------------------------------------
        */

        $data = $this->convertDates($data);

        /*
        |--------------------------------------------------------------------------
        | إعادة البيانات إلى Response
        |--------------------------------------------------------------------------
        */

        $response->setData($data);

        return $response;
    }


    /*
    |--------------------------------------------------------------------------
    | Convert Dates
    |--------------------------------------------------------------------------
    */

    private function convertDates($data)
    {
        /*
        | إذا كانت البيانات ليست Array
        */

        if (!is_array($data)) {
            return $data;
        }


        foreach ($data as $key => $value) {

            /*
            |--------------------------------------------------------------------------
            | إذا كانت القيمة Array
            |--------------------------------------------------------------------------
            */

            if (is_array($value)) {

                /*
                | إذا كانت Array تحتوي على تواريخ مباشرة
                |
                | مثال:
                |
                | "dates": [
                |     "2026-08-23",
                |     "2026-08-22",
                |     "2026-08-21"
                | ]
                */

                if ($this->isDateArray($value)) {

                    $data[$key] = array_map(
                        function ($date) {

                            return $this->convertSingleDate($date);

                        },
                        $value
                    );

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Array عادية
                |--------------------------------------------------------------------------
                |
                | نستمر في البحث داخلها عن التواريخ.
                |
                */

                $data[$key] =
                    $this->convertDates($value);

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Date Field
            |--------------------------------------------------------------------------
            |
            | إذا كان اسم الحقل يدل على أنه تاريخ.
            |
            */

            if (
                is_string($key) &&
                $this->isDateField($key) &&
                !empty($value)
            ) {

                $data[$key] =
                    $this->convertSingleDate($value);
            }
        }


        return $data;
    }


    /*
    |--------------------------------------------------------------------------
    | Convert Single Date
    |--------------------------------------------------------------------------
    */

    private function convertSingleDate($date)
    {
        try {

            return Hijrian::hijri($date)
                ->format('Y-m-d');

        } catch (\Throwable $e) {

            /*
            | إذا فشل التحويل نرجع القيمة الأصلية
            */

            return $date;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check Date Array
    |--------------------------------------------------------------------------
    */

    private function isDateArray($value)
    {
        /*
        | Array فارغة
        */

        if (empty($value)) {
            return false;
        }


        foreach ($value as $item) {

            /*
            | يجب أن تكون القيمة String
            */

            if (!is_string($item)) {
                return false;
            }


            /*
            |--------------------------------------------------------------------------
            | محاولة التحقق من أن القيمة تاريخ
            |--------------------------------------------------------------------------
            */

            try {

                Carbon::parse($item);

            } catch (\Throwable $e) {

                return false;
            }
        }


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Date Field
    |--------------------------------------------------------------------------
    */

    private function isDateField($key)
    {
        if (!is_string($key)) {
            return false;
        }

        return str_ends_with($key, '_date')
            || str_contains(
                strtolower($key),
                'date'
            );
    }
}