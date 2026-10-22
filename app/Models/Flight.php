<?php

namespace App\Models;

use App\Enums\FdrStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'aircraft_id', 'pilot_id', 'wso_id', 'flight_number', 'shift_number', 'location', 'flown_at', 'remarks',
    'duration_seconds', 'fdr_path', 'fdr_original_name', 'fdr_status', 'fdr_error', 'fdr_processed_at',
    'telemetry_path', 'stats', 'bvr_path', 'bvr_original_name', 'bvr_offset_seconds', 'created_by',
])]
class Flight extends Model
{
    protected function casts(): array
    {
        return [
            'flown_at' => 'datetime',
            'duration_seconds' => 'float',
            'shift_number' => 'integer',
            'fdr_status' => FdrStatus::class,
            'fdr_processed_at' => 'datetime',
            'stats' => 'array',
            'bvr_offset_seconds' => 'float',
        ];
    }

    public function aircraft(): BelongsTo
    {
        return $this->belongsTo(Aircraft::class);
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    public function wso(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wso_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(FlightGroup::class);
    }

    public function hasFdr(): bool
    {
        return $this->fdr_path !== null;
    }

    public function hasTelemetry(): bool
    {
        return $this->fdr_status === FdrStatus::Ready && $this->telemetry_path !== null;
    }

    public function hasBvr(): bool
    {
        return $this->bvr_path !== null;
    }

    /**
     * When the recorded data starts. Uses the timestamp inside the FDR when we
     * have one (more accurate for syncing a formation), else the flight date.
     */
    public function dataStartsAt(): CarbonInterface
    {
        $fromLog = $this->stats['start_time'] ?? null;

        return $fromLog ? Carbon::parse($fromLog) : $this->flown_at;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->whereLike('flight_number', "%{$term}%")
            ->orWhereLike('location', "%{$term}%")
            ->orWhereHas('aircraft', fn (Builder $a) => $a->whereLike('number', "%{$term}%"))
            ->orWhereHas('pilot', fn (Builder $u) => $u->whereLike('name', "%{$term}%"))
            ->orWhereHas('wso', fn (Builder $u) => $u->whereLike('name', "%{$term}%"))));
    }

    /** Relations the flight tables always need, to avoid N+1 queries. */
    public function scopeWithListRelations(Builder $query): Builder
    {
        return $query->with(['aircraft.type', 'pilot', 'wso']);
    }
}
