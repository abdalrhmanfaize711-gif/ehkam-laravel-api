<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('region')->truncate();
        DB::table('region')->insert([
            ['name' => 'عبودة'],
            ['name' => 'جوجة'],
            ['name' => 'وادي سر'],
            ['name' => 'العقاد'],
            ['name' => 'حويلة'],
            ['name' => 'خشامر'],
            ['name' => 'القارة'],
            ['name' => 'شبام'],
            ['name' => 'عقران'],
            ['name' => 'حذية'],
        ]);
    }
}
