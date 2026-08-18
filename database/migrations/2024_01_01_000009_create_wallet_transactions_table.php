<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['charge', 'purchase', 'refund', 'commission', 'referral_bonus', 'admin_adjust']);
            $table->decimal('amount', 15, 2); // signed: positive or negative
            $table->decimal('balance_after', 15, 2);
            $table->nullableMorphs('reference'); // reference_type, reference_id (Order, Payment, ...)
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
