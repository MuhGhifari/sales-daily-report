<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_settings', function (Blueprint $table) {
            $table->foreignId('team_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('working_days');               // e.g. [1,2,3,4,5,6], 0 = Sunday
            $table->unsignedTinyInteger('edit_days')->default(2);
            $table->string('reminder_time', 5)->default('20:00');
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('name');
            $table->boolean('is_working_day')->default(false);
            $table->unique(['team_id', 'date']);
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('chain')->nullable();
            $table->string('city');
            $table->string('address')->nullable();
            $table->foreignId('area_id')->nullable()->constrained();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->unsignedBigInteger('price'); // whole Rupiah
            $table->string('photo_path')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7); // 2026-10
            $table->unsignedBigInteger('monthly')->default(0);
            $table->unsignedBigInteger('weekly')->nullable(); // manual override
            $table->unsignedBigInteger('daily')->nullable();  // manual override
            $table->foreignId('set_by')->nullable()->constrained('users');
            $table->date('set_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'month']);
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('start_time', 5);
            $table->string('end_time', 5)->nullable();
            $table->foreignId('store_id')->nullable()->constrained(); // current store
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        Schema::create('shift_store_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained();
            $table->string('from_time', 5);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_id')->unique(); // generated on the phone; makes resending safe
            $table->foreignId('user_id')->constrained();
            $table->date('date');
            $table->string('time', 5)->nullable(); // null = added afterwards for a past day
            $table->foreignId('store_id')->nullable()->constrained();
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('subtotal');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'date']);
            $table->index('date');
        });

        Schema::create('day_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('no_sales')->default(false);
            $table->boolean('unlocked')->default(false);
            $table->foreignId('unlocked_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action');
            $table->string('kind');
            $table->string('subject_id')->nullable();
            $table->string('name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['activity_log', 'day_reports', 'sales', 'shift_store_visits', 'shifts', 'targets', 'products', 'stores', 'holidays', 'team_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
