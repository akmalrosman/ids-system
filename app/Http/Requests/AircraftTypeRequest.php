<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AircraftTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('aircraft_types', 'name')->ignore($this->route('aircraftType'))],
            'manufacturer' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'model_length_m' => ['required', 'numeric', 'between:0.1,500'],
            'model_yaw_offset_deg' => ['required', 'integer', 'between:-360,360'],
            // .glb = binary glTF (one file with meshes + textures)
            'model_file' => ['nullable', 'file', 'extensions:glb', 'max:102400'],
            'remove_model' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'model_length_m' => 'real length',
            'model_yaw_offset_deg' => 'heading offset',
            'model_file' => '3D model',
        ];
    }
}
