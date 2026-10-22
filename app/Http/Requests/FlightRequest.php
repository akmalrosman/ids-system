<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation for creating and updating a flight (FlightController@store/update). */
class FlightRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'aircraft_id' => ['required', 'integer', 'exists:aircraft,id'],
            'pilot_id' => ['nullable', 'integer', 'exists:users,id'],
            'wso_id' => ['nullable', 'integer', 'exists:users,id'],
            'flight_number' => ['required', 'string', 'max:50'],
            'shift_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'location' => ['nullable', 'string', 'max:100'],
            'flown_at' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:5000'],

            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:flight_groups,id'],

            'fdr' => ['nullable', 'file', 'extensions:'.implode(',', config('ids.fdr.extensions')), 'max:'.config('ids.fdr.max_upload_kb')],
            'bvr' => ['nullable', 'file', 'extensions:'.implode(',', config('ids.bvr.extensions')), 'max:'.config('ids.bvr.max_upload_kb')],
            'bvr_offset_seconds' => ['nullable', 'numeric', 'between:-86400,86400'],
        ];
    }

    public function attributes(): array
    {
        return [
            'aircraft_id' => 'aircraft',
            'pilot_id' => 'pilot',
            'wso_id' => 'WSO',
            'flown_at' => 'date',
            'fdr' => 'FDR file',
            'bvr' => 'BVR video',
            'bvr_offset_seconds' => 'video offset',
        ];
    }

    public function messages(): array
    {
        // Shown when the file is bigger than PHP's upload_max_filesize (see README "Upload limits").
        return [
            'fdr.uploaded' => 'The FDR file failed to upload. It may be larger than the server upload limit.',
            'bvr.uploaded' => 'The video failed to upload. It may be larger than the server upload limit.',
        ];
    }

    /** Only the columns stored directly on the flights table. */
    public function flightData(): array
    {
        return [
            ...$this->safe()->except(['group_ids', 'fdr', 'bvr', 'bvr_offset_seconds']),
            'bvr_offset_seconds' => $this->input('bvr_offset_seconds') ?? 0,
        ];
    }
}
