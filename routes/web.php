<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Middleware\IsTeacher;
Route::get('/', function () {
    
    return view('welcome');
});

Route::get('/hello' , function () {
    return 'my ekham pro V.1.0.0';
});












