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
        Schema::create('etqan_record', function (Blueprint $table) {
            $table->id();
        
    // الطالب
    $table->foreignId('student_id')
          ->constrained('students')
          ->cascadeOnDelete();

    // عدد الأوجه أو الصفحات التي اختبرت
    $table->decimal('num_of_sheets', 4, 2);

    $table->string('from_surah');
    $table->unsignedSmallInteger('from_ayah');

    $table->string('to_surah');
    $table->unsignedSmallInteger('to_ayah');

    $table->enum('memorization_state', [
       'حفظ',
       'لم يحفظ',
    ]);

    // تاريخ الاختبار
    $table->date('addition_date');

    // هل يدخل ضمن الربط العام؟
    $table->boolean('general_revision')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etqan_record');
    }
};
