<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'user_name' => 'Admin User',
                'user_email' => 'admin123@gmail.com',
                'user_password' => Hash::make('admin123'),
                'is_admin' => true,
            ],
            [
                'user_name' => 'John Doe',
                'user_email' => 'john@example.com',
                'user_password' => Hash::make('password123'),
                'is_admin' => false,
            ],
            [
                'user_name' => 'Jane Smith',
                'user_email' => 'jane@example.com',
                'user_password' => Hash::make('password123'),
                'is_admin' => false,
            ],
            [
                'user_name' => 'Reaksa',
                'user_email' => 'reaksa@gmail.com',
                'user_password' => Hash::make('password123'),
                'is_admin' => false,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['user_email' => $user['user_email']],
                $user
            );
        }
    }
}
