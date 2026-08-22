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
        Schema::create('record_exams', function (Blueprint $table) {
            $table->id();

    $table->foreignId('student_id')
          ->constrained('students')
          ->cascadeOnDelete();

    $table->foreignId('teacher_id')
          ->constrained('teachers')
          ->cascadeOnDelete();

    $table->unsignedTinyInteger('num_of_questions');

    $table->unsignedTinyInteger('total_mistakes');

    $table->unsignedTinyInteger('total_melodies');

    $table->unsignedTinyInteger('total_hesitiations');

    $table->decimal('final_percentage',5,2);

    $table->string('exam_type');

    $table->date('insert_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_exams');
    }
};
