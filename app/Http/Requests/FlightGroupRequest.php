<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightGroupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:100'],
            'flown_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'flight_ids' => ['nullable', 'array'],
            'flight_ids.*' => ['integer', 'exists:flights,id'],
        ];
    }

    public function attributes(): array
    {
        return ['flown_at' => 'date', 'flight_ids' => 'flights'];
    }
}
