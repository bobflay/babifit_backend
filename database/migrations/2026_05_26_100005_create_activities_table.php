<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type');                 // Walk, Cycling, Jog, ...
            $table->unsignedInteger('kcal')->default(0);
            $table->unsignedInteger('mins')->default(0);
            $table->string('distance')->nullable(); // display string e.g. "4.1 km"
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
