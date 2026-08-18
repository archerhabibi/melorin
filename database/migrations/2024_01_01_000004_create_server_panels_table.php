<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_panels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('panel_type', ['sanaei', 'marzban', 'pasarguard', 'other'])->default('marzban');
            $table->string('host');
            $table->integer('port');
            $table->text('credentials'); // encrypted via model cast
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('capacity')->nullable();
            $table->integer('account_limit_per_user')->nullable();
            $table->enum('health_status', ['healthy', 'degraded', 'down'])->nullable();
            $table->unsignedInteger('active_accounts_count')->default(0);
            $table->json('extra_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_panels');
    }
};
