{{-- Shared by flights/create and flights/edit. Expects $flight, $aircraft, $aircrew, $groups, $locations, $selectedGroups. --}}
@php
    $fdrExt = '.'.implode(',.', config('ids.fdr.extensions'));
    $bvrExt = '.'.implode(',.', config('ids.bvr.extensions'));
    $selectedGroups = old('group_ids', $selectedGroups);
@endphp

@csrf

<div class="grid gap-6 lg:grid-cols-3">
    <x-ui.card title="Flight details" class="lg:col-span-2">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.field label="Aircraft" name="aircraft_id" required>
                <select id="aircraft_id" name="aircraft_id" class="form-control" required>
                    <option value="">Select aircraft…</option>
                    @foreach ($aircraft as $a)
                        <option value="{{ $a->id }}" @selected(old('aircraft_id', $flight->aircraft_id) == $a->id)>{{ $a->label() }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Date & time" name="flown_at" required hint="Take-off time (local).">
                <input id="flown_at" name="flown_at" type="datetime-local" step="1" required class="form-control"
                       value="{{ old('flown_at', $flight->flown_at?->format('Y-m-d\TH:i:s')) }}">
            </x-ui.field>

            <x-ui.field label="Flight number" name="flight_number" required>
                <input id="flight_number" name="flight_number" required maxlength="50" class="form-control"
                       value="{{ old('flight_number', $flight->flight_number) }}">
            </x-ui.field>

            <x-ui.field label="Shift number" name="shift_number">
                <input id="shift_number" name="shift_number" type="number" min="1" max="99" class="form-control"
                       value="{{ old('shift_number', $flight->shift_number) }}">
            </x-ui.field>

            <x-ui.field label="Pilot" name="pilot_id">
                <select id="pilot_id" name="pilot_id" class="form-control">
                    <option value="">—</option>
                    @foreach ($aircrew as $u)
                        <option value="{{ $u->id }}" @selected(old('pilot_id', $flight->pilot_id) == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="WSO" name="wso_id" hint="Weapon Systems Officer (back seat).">
                <select id="wso_id" name="wso_id" class="form-control">
                    <option value="">—</option>
                    @foreach ($aircrew as $u)
                        <option value="{{ $u->id }}" @selected(old('wso_id', $flight->wso_id) == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Location" name="location" class="sm:col-span-2">
                <input id="location" name="location" list="locations" maxlength="100" class="form-control"
                       value="{{ old('location', $flight->location) }}" placeholder="e.g. Langkawi">
                <datalist id="locations">
                    @foreach ($locations as $loc)<option value="{{ $loc }}">@endforeach
                </datalist>
            </x-ui.field>

            <x-ui.field label="Remarks" name="remarks" class="sm:col-span-2">
                <textarea id="remarks" name="remarks" rows="3" class="form-control">{{ old('remarks', $flight->remarks) }}</textarea>
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="space-y-6">
        <x-ui.card title="FDR file" description="Flight data. DJI flight record (.txt / .csv) for now.">
            @if ($flight->hasFdr())
                <div class="mb-4 flex items-center gap-2 text-sm">
                    <x-icon name="file-text" class="size-4 text-neutral-400" />
                    <span class="truncate">{{ $flight->fdr_original_name }}</span>
                    <x-ui.badge :variant="$flight->fdr_status->variant()">{{ $flight->fdr_status->label() }}</x-ui.badge>
                </div>
            @endif
            <x-ui.field :label="$flight->hasFdr() ? 'Replace file' : 'Upload file'" name="fdr"
                        hint="Converted to telemetry JSON automatically after saving.">
                <input id="fdr" name="fdr" type="file" accept="{{ $fdrExt }}" class="form-control">
            </x-ui.field>
        </x-ui.card>

        <x-ui.card title="BVR video" description="Recording shown beside the aircraft during presentation.">
            @if ($flight->hasBvr())
                <div class="mb-4 flex items-center gap-2 text-sm">
                    <x-icon name="file-video" class="size-4 text-neutral-400" />
                    <span class="truncate">{{ $flight->bvr_original_name }}</span>
                </div>
            @endif
            <div class="space-y-4">
                <x-ui.field :label="$flight->hasBvr() ? 'Replace video' : 'Upload video'" name="bvr">
                    <input id="bvr" name="bvr" type="file" accept="{{ $bvrExt }}" class="form-control">
                </x-ui.field>
                <x-ui.field label="Video offset (seconds)" name="bvr_offset_seconds"
                            hint="Flight time at which the video starts. Use a negative value if the video starts before the log.">
                    <input id="bvr_offset_seconds" name="bvr_offset_seconds" type="number" step="0.001" class="form-control"
                           value="{{ old('bvr_offset_seconds', $flight->bvr_offset_seconds ?? 0) }}">
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card title="Groups / Formations">
            <div class="max-h-56 space-y-2 overflow-y-auto">
                @forelse ($groups as $group)
                    <label class="flex items-center gap-3 text-sm">
                        <input type="checkbox" name="group_ids[]" value="{{ $group->id }}" @checked(in_array($group->id, $selectedGroups))
                               class="size-4 accent-neutral-100">
                        <span>{{ $group->title }}</span>
                        <span class="ml-auto text-xs text-neutral-500">{{ $group->flown_at->format('j M Y') }}</span>
                    </label>
                @empty
                    <p class="text-sm text-neutral-500">No groups yet.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</div>
