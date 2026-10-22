<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'manufacturer', 'model', 'description', 'model_path', 'model_length_m', 'model_yaw_offset_deg'])]
class AircraftType extends Model
{
    protected function casts(): array
    {
        return [
            'model_length_m' => 'float',
            'model_yaw_offset_deg' => 'integer',
        ];
    }

    public function aircraft(): HasMany
    {
        return $this->hasMany(Aircraft::class);
    }

    /** URL of the .glb model to render in the presentation. */
    public function modelUrl(): string
    {
        return $this->model_path
            ? route('aircraft-types.model', $this)
            : config('ids.default_model.url');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->whereLike('name', "%{$term}%")
            ->orWhereLike('manufacturer', "%{$term}%")
            ->orWhereLike('model', "%{$term}%")));
    }
}
