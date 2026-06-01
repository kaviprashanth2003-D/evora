<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Vulnerability #1 Fix: Read credentials from Laravel environment variables
        $email = env('ADMIN_EMAIL', 'admin@evoraa.com');
        $password = env('ADMIN_PASSWORD', 'ChangeThisPassword123!');
        $name = env('ADMIN_NAME', 'Main Admin');

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password_hash' => Hash::make($password),
            ]
        );
    }
}
