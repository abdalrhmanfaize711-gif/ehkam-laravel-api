<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('sard_sessions_records', function (Blueprint $table) {


            $table->id();


            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            $table->foreignId('record_sard_day_id')
                  ->constrained('record_sard_days')
                  ->cascadeOnDelete();

             $table->unsignedTinyInteger('total_mistakes')->nullable();
        
             $table->unsignedTinyInteger('total_melodies')->nullable();
        
  $table->enum('hesitiations_state', [
        'مرتفع',
        'متوسط ',
        'منخفض'
    ]);
            $table->string('serd_day');
    // بداية الحفظ
    $table->string('from_surah');
    $table->unsignedSmallInteger('from_ayah');

    // نهاية الحفظ
    $table->string('to_surah');
    $table->unsignedSmallInteger('to_ayah');

   $table->enum('session_state', [
        'أكمل',
        'لم يكمل',
    ]);

            $table->integer('session_number');


            $table->date('sard_date');
            $table->date('insert_date');


            $table->timestamps();


        });

    }



    public function down(): void
    {

        Schema::dropIfExists('sard_sessions_records');

    }

};