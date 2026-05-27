<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Async OCR jobs behind POST /scans/upload -> GET /scans/parse/{jobId}.
        Schema::create('scan_parse_jobs', function (Blueprint $table) {
            $table->string('id')->primary(); // "job_abc"
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('photo_id')->nullable();
            $table->foreign('photo_id')->references('id')->on('photos')->nullOnDelete();
            $table->enum('status', ['processing', 'done', 'failed'])->default('processing');
            $table->decimal('confidence', 4, 2)->nullable();
            $table->json('draft')->nullable();   // unsaved scan draft (shape of GET /scans/{id})
            $table->text('insight')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_parse_jobs');
    }
};
