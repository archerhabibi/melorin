<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('server_panel_id')->constrained();
            // nullable چون بعضی محصولات (products.protocol_id) پروتکل مشخصی ندارند —
            // این ستون باید همیشه با products.protocol_id هماهنگ بماند
            $table->foreignId('protocol_id')->nullable()->constrained();
            $table->text('config_data'); // encrypted via model cast
            $table->dateTime('starts_at');
            $table->dateTime('expires_at');
            $table->decimal('traffic_gb', 10, 2)->nullable();
            $table->decimal('traffic_used_gb', 10, 2)->nullable();
            $table->enum('status', ['active', 'expired', 'disabled', 'deleted', 'suspended'])->default('active');
            $table->boolean('is_test')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
