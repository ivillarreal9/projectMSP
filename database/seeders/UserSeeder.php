<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario Admin
        User::create([
            'name'     => 'Irving Villarreal',
            'email'    => 'ivillarreal@ovni.com',
            'password' => Hash::make('Irving09*.'),
            'role'     => 'admin',
        ]);
    }
}