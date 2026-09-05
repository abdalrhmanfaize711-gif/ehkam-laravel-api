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
        Schema::create('scheduled_sard_stage', function (Blueprint $table) {
          $table->id();
          $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

          $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

          $table->enum('sard_type',[
                'سرد أول',
                'سرد ثاني'
               ]);

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
        Schema::dropIfExists('scheduled_sard_stage');
    }
};
