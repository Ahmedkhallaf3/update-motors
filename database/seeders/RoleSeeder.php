<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Role::create([
        //     'name' => 'admin',
        //     'guard_name' => 'web'
        // ]);
        if (!Role::where('name', 'admin')->where('guard_name', 'web')->exists()) {
            Role::create([
                'name' => 'admin',
                'guard_name' => 'web'
            ]);
        }
    }
}
