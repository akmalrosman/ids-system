@csrf
<div class="grid max-w-4xl gap-6 lg:grid-cols-2">
    <x-ui.card title="Details">
        <div class="space-y-5">
            <x-ui.field label="Name" name="name" required hint="e.g. Sukhoi Su-30MKM">
                <input id="name" name="name" required maxlength="100" class="form-control" value="{{ old('name', $type->name) }}">
            </x-ui.field>
            <x-ui.field label="Manufacturer" name="manufacturer" required>
                <input id="manufacturer" name="manufacturer" required maxlength="100" class="form-control" value="{{ old('manufacturer', $type->manufacturer) }}">
            </x-ui.field>
            <x-ui.field label="Model" name="model" required>
                <input id="model" name="model" required maxlength="100" class="form-control" value="{{ old('model', $type->model) }}">
            </x-ui.field>
            <x-ui.field label="Description" name="description">
                <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $type->description) }}</textarea>
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card title="3D model" description="Shown in the presentation. Without an upload the default Su-30 model is used.">
        <div class="space-y-5">
            @if ($type->model_path)
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="remove_model" value="1" class="size-4 accent-neutral-100">
                    Remove uploaded model (use default)
                </label>
            @endif
            <x-ui.field label="Model file (.glb)" name="model_file" hint="Binary glTF. Export from Blender: File → Export → glTF 2.0 (.glb).">
                <input id="model_file" name="model_file" type="file" accept=".glb" class="form-control">
            </x-ui.field>
            <x-ui.field label="Real length (m)" name="model_length_m" required hint="The model is scaled so its longest side is this long.">
                <input id="model_length_m" name="model_length_m" type="number" step="0.01" min="0.1" required class="form-control"
                       value="{{ old('model_length_m', $type->model_length_m) }}">
            </x-ui.field>
            <x-ui.field label="Heading offset (°)" name="model_yaw_offset_deg" required
                        hint="Rotation needed so the nose points forward. Try 90, -90 or 180 if the aircraft flies sideways or backwards.">
                <input id="model_yaw_offset_deg" name="model_yaw_offset_deg" type="number" step="1" min="-360" max="360" required class="form-control"
                       value="{{ old('model_yaw_offset_deg', $type->model_yaw_offset_deg) }}">
            </x-ui.field>
        </div>
    </x-ui.card>
</div>
