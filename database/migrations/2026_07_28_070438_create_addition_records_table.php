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
      Schema::create('addition_records', function (Blueprint $table) {

    $table->id();

    // الطالب
    $table->foreignId('student_id')
          ->constrained('students')
          ->cascadeOnDelete();

    // عدد الصفحات
    $table->decimal('num_of_pages', 4, 2);

    // بداية الحفظ
    $table->string('from_surah');
    $table->unsignedSmallInteger('from_ayah');

    // نهاية الحفظ
    $table->string('to_surah');
    $table->unsignedSmallInteger('to_ayah');

    // عدد مرات الإعادة
    $table->unsignedSmallInteger('repeated_times');

    // حالة الحفظ
    $table->enum('memorization_state', [
        'حفظ',
        'لم يحفظ',
    ]);

    
   
    // تاريخ الإضافة
    $table->date('addition_date');

    // ربط العام
    $table->boolean('general_revision')->default(false);

    // ربط اليوم
    $table->boolean('daily_revision')->default(false);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addition_records');
    }
};
