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
        Schema::create('pledges_record', function (Blueprint $table) {
            $table->id();
          
          
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
          
            $table->enum('pledge_type', [
                'تعهد ربط عام',    
                'تعهد إضافة',  
                'تعهد نظام', 
                 'تعهد غياب',             
            ]);
          
            $table->date('insert_date');
          
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pledges_record');
    }
};
