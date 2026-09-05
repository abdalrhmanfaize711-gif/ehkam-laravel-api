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
        Schema::create('scheduled_sard_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
            $table->foreignId('serd_schedule_id')
                  ->constrained('serd_schedules')
                  ->cascadeOnDelete();

                  // اليوم الأول - الثاني - الثالث...
             $table->unsignedSmallInteger('sard_day');

                // عدد الجلسات في هذا اليوم
             $table->unsignedTinyInteger('num_of_sessions');

                 // المعلم المسؤول
            $table->foreignId('teacher_id')
                  ->constrained('teachers')
                  ->cascadeOnDelete();
        
        
            // بداية السرد
            $table->string('from_surah');
            $table->unsignedSmallInteger('from_ayah');
        
            // نهاية السرد
            $table->string('to_surah');
            $table->unsignedSmallInteger('to_ayah');
            
            // تاريخ الجلسة
            $table->time('time_assigned');
            $table->date('sard_date');
            $table->date('insert_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sard_day');
    }
};
