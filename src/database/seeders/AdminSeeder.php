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
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => Hash::make('ehkamadmin123'),
        ]);
          AdminModel::create([
            'name' => 'Administrator2',
            'username' => 'admin',
            'password' => Hash::make('superadmin'),
        ]);
    }
}