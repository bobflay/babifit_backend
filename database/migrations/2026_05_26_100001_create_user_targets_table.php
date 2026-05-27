<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One-to-one with users: mirrors the Dart `UserTarget` model.
        Schema::create('user_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('calories')->default(0);
            $table->unsignedInteger('protein')->default(0);
            $table->unsignedInteger('carbs')->default(0);
            $table->unsignedInteger('fat')->default(0);
            $table->unsignedInteger('burn')->default(0);   // daily burn target (kcal)
            $table->decimal('weight', 6, 1)->nullable();    // goal weight (kg)
            $table->decimal('fat_pct', 5, 1)->nullable();   // goal body-fat %
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_targets');
    }
};
