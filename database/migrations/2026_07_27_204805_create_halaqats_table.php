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
        Schema::create('halaqats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')
              ->constrained('teachers')
              ->cascadeOnDelete();

            $table->date('insert_date');

            $table->integer('max_students');

            $table->string('halaqa_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('halaqats');
    }
};
