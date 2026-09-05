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
        Schema::create('record_sard_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
             $table->enum('sard_type',[
                'سرد أول',
                'سرد ثاني'
               ]);

            $table->unsignedSmallInteger('total_melodies')->nullable();
            $table->unsignedSmallInteger('repeat_times')->nullable();
            $table->unsignedSmallInteger('total_mistakes')->nullable();
            $table->enum('hesitation_state', [
                   'مرتفع',
                   'متوسط',
                    'منخفض',
                 ]);
            $table->enum('memorization_state', [
                   'حفظ',
                   'لم يحفظ',
                 ]);
 
            $table->time('sard_duration');
            $table->date('insert_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_sard_stages');
    }
};
