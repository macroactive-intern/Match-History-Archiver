<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('archived_matches', function (Blueprint $table) {
            $table->id();
            $table->string('match_uuid')->unique();
            $table->string('game_slug');
            $table->timestamp('played_at');
            $table->json('payload');
            $table->enum('status', ['pending', 'processing', 'archived', 'failed'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_matches');
    }
};
