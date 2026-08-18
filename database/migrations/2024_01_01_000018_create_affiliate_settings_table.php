<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('customer_bonus_amount', 15, 2)->default(0);
            $table->decimal('referrer_bonus_amount', 15, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->integer('commission_validity_days')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_settings');
    }
};
