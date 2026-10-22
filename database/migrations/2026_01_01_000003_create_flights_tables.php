<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aircraft_id')->constrained('aircraft')->restrictOnDelete();
            $table->foreignId('pilot_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('wso_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('flight_number');
            $table->unsignedSmallInteger('shift_number')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('flown_at')->index();
            // Filled in from the FDR once it has been processed.
            $table->decimal('duration_seconds', 12, 3)->nullable();
            $table->text('remarks')->nullable();

            // FDR = flight data file (DJI .txt for now, aircraft .tkd later).
            $table->string('fdr_path')->nullable();
            $table->string('fdr_original_name')->nullable();
            $table->string('fdr_status')->default('none'); // App\Enums\FdrStatus
            $table->text('fdr_error')->nullable();
            $table->timestamp('fdr_processed_at')->nullable();
            // Normalised telemetry JSON produced by fdr-parser.
            $table->string('telemetry_path')->nullable();
            // Summary (max altitude, distance, bounds, start time...) copied from the telemetry.
            $table->json('stats')->nullable();

            // BVR = video recording shown next to the aircraft during presentation.
            $table->string('bvr_path')->nullable();
            $table->string('bvr_original_name')->nullable();
            // Seconds into the flight at which the video starts (can be negative).
            $table->decimal('bvr_offset_seconds', 10, 3)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('flight_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('location')->nullable();
            $table->dateTime('flown_at')->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('flight_flight_group', function (Blueprint $table) {
            $table->foreignId('flight_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flight_id')->constrained()->cascadeOnDelete();
            $table->primary(['flight_group_id', 'flight_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_flight_group');
        Schema::dropIfExists('flight_groups');
        Schema::dropIfExists('flights');
    }
};
