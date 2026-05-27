<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // InBody scans. String PK so seeds can use friendly ids ("scan-1");
        // new rows get a ULID. Deltas / previousScanId are derived at runtime.
        Schema::create('scans', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('label')->nullable(); // e.g. "26 May"; derived if null

            // Headline composition
            $table->decimal('weight', 6, 1);    // kg
            $table->decimal('muscle', 6, 1);    // kg
            $table->decimal('fat', 6, 1);       // kg of fat mass
            $table->decimal('fat_pct', 5, 1);   // %
            $table->decimal('bmi', 5, 1);
            $table->decimal('health', 5, 1);    // health score

            // Detail metrics
            $table->decimal('bmr', 8, 1)->nullable();        // kcal
            $table->decimal('water', 6, 1)->nullable();      // L
            $table->unsignedSmallInteger('visceral')->nullable();
            $table->decimal('waist_hip', 4, 2)->nullable();
            $table->decimal('protein', 6, 1)->nullable();    // kg
            $table->decimal('salt', 6, 1)->nullable();       // g
            $table->decimal('lean_mass', 6, 1)->nullable();  // kg
            $table->decimal('intra_water', 6, 1)->nullable();
            $table->decimal('extra_water', 6, 1)->nullable();
            $table->unsignedInteger('abdominal_fat')->nullable();
            $table->unsignedInteger('subcut_fat')->nullable();
            $table->unsignedInteger('burn_rec')->nullable(); // recommended daily burn

            // Nested structures rendered by the scan-detail screen
            $table->json('ranges')->nullable();    // { weight:[min,low,high,max], ... }
            $table->json('segments')->nullable();  // { armR:{muscle,fat}, ... }
            $table->json('nutrition')->nullable(); // { protein:'low', fat:'high', salt:'high' }

            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
