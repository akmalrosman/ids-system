<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['aircraft_type_id', 'number', 'registration', 'serial', 'notes'])]
class Aircraft extends Model
{
    protected $table = 'aircraft';

    public function type(): BelongsTo
    {
        return $this->belongsTo(AircraftType::class, 'aircraft_type_id');
    }

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }

    /** "Su-30MKM - 5210" */
    public function label(): string
    {
        return trim(($this->type?->model ?? '').' - '.$this->number, ' -');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->whereLike('number', "%{$term}%")
            ->orWhereLike('registration', "%{$term}%")
            ->orWhereLike('serial', "%{$term}%")
            ->orWhereHas('type', fn (Builder $t) => $t->search($term))));
    }
}
