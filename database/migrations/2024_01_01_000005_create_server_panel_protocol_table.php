<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_panel_protocol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_panel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('protocol_id')->constrained()->cascadeOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['server_panel_id', 'protocol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_panel_protocol');
    }
};
