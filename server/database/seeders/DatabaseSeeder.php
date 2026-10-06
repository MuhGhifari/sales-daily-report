<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data for development and client demos. A real installation starts empty:
     * php artisan migrate && php artisan app:create-admin
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
