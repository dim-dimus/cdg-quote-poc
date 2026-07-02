<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default front-desk login for the POC (documented in the README).
        User::updateOrCreate(
            ['email' => 'staff@calidreamgarage.test'],
            ['name' => 'Front Desk', 'password' => Hash::make('password')],
        );

        $this->call(WorkbookSeeder::class);
    }
}
