<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A group / formation: several flights presented together on one timeline.
 */
#[Fillable(['title', 'location', 'flown_at', 'description', 'created_by'])]
class FlightGroup extends Model
{
    protected function casts(): array
    {
        return [
            'flown_at' => 'datetime',
        ];
    }

    public function flights(): BelongsToMany
    {
        return $this->belongsToMany(Flight::class)->orderBy('flown_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Time from the first aircraft starting to the last one finishing.
     * Requires the `flights` relation to be loaded.
     */
    public function durationSeconds(): ?float
    {
        $flights = $this->flights->filter(fn (Flight $f) => $f->duration_seconds !== null);

        if ($flights->isEmpty()) {
            return null;
        }

        $start = $flights->min(fn (Flight $f) => $f->dataStartsAt()->getTimestampMs());
        $end = $flights->max(fn (Flight $f) => $f->dataStartsAt()->getTimestampMs() + $f->duration_seconds * 1000);

        return ($end - $start) / 1000;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->whereLike('title', "%{$term}%")
            ->orWhereLike('location', "%{$term}%")));
    }
}
