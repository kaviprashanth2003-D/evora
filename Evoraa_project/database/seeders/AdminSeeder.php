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
        $email = env('ADMIN_EMAIL', 'admin@gmail.com');
        $password = env('ADMIN_PASSWORD', 'admin1234');
        $name = env('ADMIN_NAME', 'Administrator');

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password_hash' => Hash::make($password),
            ]
        );
    }
}
