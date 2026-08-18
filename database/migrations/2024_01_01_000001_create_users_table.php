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
            $table->bigInteger('telegram_id')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('username_site')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('full_name')->nullable();
            $table->enum('status', ['active', 'disabled', 'blocked'])->default('active');
            $table->foreignId('referrer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('joined_from', ['bot', 'website', 'reseller_bot', 'panel'])->default('bot');
            // FK to resellers added later (migration 000011) once resellers table exists
            $table->unsignedBigInteger('reseller_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
