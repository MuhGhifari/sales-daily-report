<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Console\Command;

/** First account of a new installation (Admin then adds Supervisors and Team Leaders in the app). */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {phone} {name=Admin} {--area=Jabodetabek : Name of the first area}';

    protected $description = 'Create an Admin account (prompts for the password) and the first area';

    public function handle(): int
    {
        $phone = Clock::phone($this->argument('phone'));
        if (! preg_match('/^08\d{8,11}$/', $phone)) {
            $this->error('Nomor HP tidak valid (contoh: 0812 3456 7890).');

            return self::FAILURE;
        }
        if (User::where('phone', $phone)->exists()) {
            $this->error('Nomor HP sudah terdaftar.');

            return self::FAILURE;
        }
        $password = $this->secret('Password (min. 8 karakter)');
        if (strlen((string) $password) < 8 || $password !== $this->secret('Ulangi password')) {
            $this->error('Password terlalu pendek atau tidak sama.');

            return self::FAILURE;
        }
        Area::firstOrCreate(['name' => $this->option('area')]);
        User::create(['phone' => $phone, 'name' => $this->argument('name'), 'role' => 'admin', 'password' => $password, 'active' => true]);
        $this->info("Admin {$phone} dibuat. Login di halaman utama dengan nomor HP ini.");

        return self::SUCCESS;
    }
}
