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
        Schema::create('scheduled_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
          ->constrained('students')
          ->cascadeOnDelete();

    $table->foreignId('teacher_id')
          ->constrained('teachers')
          ->cascadeOnDelete();

    $table->date('start_date');
    $table->date('end_date');

    $table->unsignedTinyInteger('num_of_juz');

    $table->string('from_surah');
    $table->unsignedSmallInteger('from_ayah');

    $table->string('to_surah');
    $table->unsignedSmallInteger('to_ayah');

    $table->unsignedTinyInteger('num_of_questions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_exams');
    }
};
