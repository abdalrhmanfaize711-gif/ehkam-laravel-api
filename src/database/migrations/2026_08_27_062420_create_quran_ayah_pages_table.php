<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_ayah_pages', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Quran Surah Number
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('surah_number');


            /*
            |--------------------------------------------------------------------------
            | Ayah Number Inside Surah
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('ayah_number');


            /*
            |--------------------------------------------------------------------------
            | Madinah Mushaf Page
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('page_number');


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate ayahs
            |--------------------------------------------------------------------------
            */

            $table->unique(
                ['surah_number', 'ayah_number'],
                'quran_ayah_unique'
            );


            /*
            |--------------------------------------------------------------------------
            | Fast lookup
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['surah_number', 'ayah_number'],
                'quran_ayah_lookup'
            );


            $table->index(
                'page_number',
                'quran_page_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_ayah_pages');
    }
};