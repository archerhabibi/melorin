<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('reseller_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('sales_channel', ['main_bot', 'reseller_bot', 'website', 'panel'])->default('main_bot');
            $table->decimal('base_price', 15, 2);
            $table->decimal('sold_price', 15, 2);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'paid', 'account_created', 'failed', 'refunded'])->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
