<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminUsername = strtolower(trim((string) env('ADMIN_USERNAME', env('ADMIN_EMAIL', 'admin'))));
        $adminPassword = (string) env('ADMIN_PASSWORD', 'Admin123456*');

        $exists = DB::table('user')->where('username', $adminUsername)->exists();
        if (!$exists) {
            DB::table('user')->insert([
                'id' => Uuid::uuid4()->toString(),
                'username' => $adminUsername,
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
                'role' => 'admin',
            ]);
        }
    }
}
