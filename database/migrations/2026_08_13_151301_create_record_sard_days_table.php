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
        Schema::create('record_sard_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            $table->foreignId('sard_record_id')
                  ->constrained('sard_records')
                  ->cascadeOnDelete();

            $table->unsignedSmallInteger('num_of_session');
            $table->string('sard_day');
            $table->unsignedSmallInteger('num_of_remaining_sheets')->default(0);
           $table->unique([
               'student_id',
               'sard_day'
           ]);
                       $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_sard_days');
    }
};
