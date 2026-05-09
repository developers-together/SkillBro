<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo role accounts for local SPA QA.
        User::query()->updateOrCreate(
            ['email' => 'student@skillbro.test'],
            [
                'name' => 'SkillBro Student',
                'password' => 'Password123!',
                'role' => UserRole::Student,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'instructor@skillbro.test'],
            [
                'name' => 'SkillBro Instructor',
                'password' => 'Password123!',
                'role' => UserRole::Instructor,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@skillbro.test'],
            [
                'name' => 'SkillBro Admin',
                'password' => 'Password123!',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
