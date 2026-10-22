<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AircraftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'aircraft_type_id' => ['required', 'integer', 'exists:aircraft_types,id'],
            'number' => ['required', 'string', 'max:30', Rule::unique('aircraft', 'number')->ignore($this->route('aircraft'))],
            'registration' => ['nullable', 'string', 'max:50'],
            'serial' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['aircraft_type_id' => 'aircraft type'];
    }
}
