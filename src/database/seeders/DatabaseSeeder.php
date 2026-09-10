<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Delete old seeded data
        DB::table('admin')->delete();
        DB::table('region')->delete();
        DB::table('quran_ayah_pages')->delete();

        // Run seeders
        $this->call([
            QuranAyahPageSeeder::class,
            AdminSeeder::class,
            RegionsSeeder::class,
        ]);

        // Create test user only if it doesn't exist
        
    }
}