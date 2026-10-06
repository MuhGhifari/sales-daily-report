<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique(); // login, stored as 08…
            $table->string('name');
            $table->enum('role', ['spg', 'leader', 'supervisor', 'admin']);
            $table->foreignId('area_id')->nullable()->constrained();
            $table->foreignId('team_id')->nullable()->constrained();
            $table->unsignedBigInteger('home_store_id')->nullable(); // usual store, pre-selected at shift start
            $table->string('photo_path')->nullable();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
