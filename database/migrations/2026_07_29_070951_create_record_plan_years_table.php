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
        Schema::create('record_plan_years', function (Blueprint $table) {
            $table->id();
             $table->foreignId('student_id')
                   ->constrained('students')
                   ->cascadeOnDelete();

    $table->date('start_date');

    // الحد الأدنى المطلوب
    $table->unsignedTinyInteger('min_juz_target');

    $table->string('min_from_surah');

    $table->unsignedSmallInteger('min_from_ayah');

    $table->string('min_to_surah');

    $table->unsignedSmallInteger('min_to_ayah');

    // الهدف السنوي
    $table->unsignedTinyInteger('ideal_juz_target');

    $table->string('ideal_from_surah');

    $table->unsignedSmallInteger('ideal_from_ayah');

    $table->string('ideal_to_surah');
         

    $table->unsignedSmallInteger('ideal_to_ayah');
    $table->date('insert_date');            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_plan_years');
    }
};
