<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_logs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('time', 5);              // "HH:MM" — kept as a display string
            $table->string('machine');              // Chest Press, Leg Press, ...
            $table->json('muscles')->nullable();    // AI-detected target muscle groups
            $table->unsignedSmallInteger('sets')->default(1);
            $table->unsignedSmallInteger('reps')->default(1);
            $table->unsignedInteger('kcal')->default(0);
            $table->string('photo_id')->nullable();
            $table->foreign('photo_id')->references('id')->on('photos')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_logs');
    }
};
