<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminModel;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        AdminModel::create([
            'username' => 'admin',
            'password' => Hash::make('ehkamadmin123'),
        ]);
          AdminModel::create([
            'username' => 'ehkam',
            'password' => Hash::make('superadmin'),
        ]);
    }
}