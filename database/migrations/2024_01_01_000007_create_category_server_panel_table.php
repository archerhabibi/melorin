<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_server_panel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_panel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'server_panel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_server_panel');
    }
};
