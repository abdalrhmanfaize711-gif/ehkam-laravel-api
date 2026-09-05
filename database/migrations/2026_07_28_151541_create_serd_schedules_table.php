<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('serd_schedules', function (Blueprint $table) {
            $table->id();
             $table->foreignId('student_id')
          ->constrained('students')
          ->cascadeOnDelete();

          $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

    // إجمالي الأجزاء المطلوب سردها
    $table->unsignedTinyInteger('total_assigned_juz');

    // عدد الأيام المخصصة للسرد
    $table->unsignedSmallInteger('num_of_days');

    // تاريخ إنشاء الخطة
    $table->date('insert_date');

    $table->timestamps();
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serd_schedules');
    }
};
