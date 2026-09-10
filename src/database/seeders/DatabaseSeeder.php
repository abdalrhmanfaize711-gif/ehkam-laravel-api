<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

       // Delete old seeded data
        DB::table('admin')->delete();
        DB::table('region')->delete();
        DB::table('quran_ayah_pages')->delete();
        // User::factory(10)->create();
           $this->call([
            QuranAyahPageSeeder::class,
             AdminSeeder::class,
            RegionsSeeder::class,
        ]);
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    }
}
