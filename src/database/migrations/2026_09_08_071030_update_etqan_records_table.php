<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etqan_record', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_mistakes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('etqan_records', function (Blueprint $table) {
            $table->dropColumn('total_mistakes');
        });
    }
};