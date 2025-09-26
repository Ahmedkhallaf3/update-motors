<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where('email', 'admin@admin.com')->exists()) {
            $admin = User::create([
                'name' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('123456789'),
            ]);

            $admin->assignRole('admin'); // Assign role only if the user is newly created
        }
    }
}
