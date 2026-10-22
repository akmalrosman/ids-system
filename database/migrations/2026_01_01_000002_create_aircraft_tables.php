<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aircraft_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('manufacturer');
            $table->string('model');
            $table->text('description')->nullable();
            // Optional .glb 3D model; falls back to config('ids.default_model').
            $table->string('model_path')->nullable();
            // Real-world length used to scale the 3D model.
            $table->decimal('model_length_m', 8, 2)->default(21.9);
            // Rotation that makes the model's nose point north at heading 0.
            $table->smallInteger('model_yaw_offset_deg')->default(0);
            $table->timestamps();
        });

        // "aircraft" is both singular and plural.
        Schema::create('aircraft', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aircraft_type_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->string('registration')->nullable();
            $table->string('serial')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aircraft');
        Schema::dropIfExists('aircraft_types');
    }
};
