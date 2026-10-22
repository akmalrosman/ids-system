@csrf
<x-ui.card class="max-w-2xl">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field label="Number" name="number" required hint="Tail number, e.g. 5201">
            <input id="number" name="number" required maxlength="30" class="form-control" value="{{ old('number', $aircraft->number) }}">
        </x-ui.field>
        <x-ui.field label="Aircraft type" name="aircraft_type_id" required>
            <select id="aircraft_type_id" name="aircraft_type_id" required class="form-control">
                <option value="">Select type…</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected(old('aircraft_type_id', $aircraft->aircraft_type_id) == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </x-ui.field>
        <x-ui.field label="Registration" name="registration">
            <input id="registration" name="registration" maxlength="50" class="form-control" value="{{ old('registration', $aircraft->registration) }}">
        </x-ui.field>
        <x-ui.field label="Serial" name="serial">
            <input id="serial" name="serial" maxlength="50" class="form-control" value="{{ old('serial', $aircraft->serial) }}">
        </x-ui.field>
        <x-ui.field label="Notes" name="notes" class="sm:col-span-2">
            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $aircraft->notes) }}</textarea>
        </x-ui.field>
    </div>
</x-ui.card>
