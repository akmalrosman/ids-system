@php $checked = old('permissions', $role->permissions ?? []); @endphp
@csrf
<div class="grid max-w-4xl gap-6 lg:grid-cols-2">
    <x-ui.card title="Role">
        <div class="space-y-5">
            <x-ui.field label="Name" name="name" required>
                <input id="name" name="name" required maxlength="50" class="form-control" value="{{ old('name', $role->name) }}">
            </x-ui.field>
            <x-ui.field label="Description" name="description">
                <input id="description" name="description" maxlength="255" class="form-control" value="{{ old('description', $role->description) }}">
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card title="Permissions">
        @if ($role->isSuperAdmin())
            <p class="text-sm text-neutral-400">Super Admin always has every permission.</p>
        @else
            <div class="space-y-5">
                @foreach (\App\Support\Permission::grouped() as $section => $permissions)
                    <div>
                        <p class="mb-2 text-xs font-medium tracking-wide text-neutral-500 uppercase">{{ $section }}</p>
                        <div class="space-y-2">
                            @foreach ($permissions as $key => $label)
                                <label class="flex items-start gap-3 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $checked))
                                           class="mt-0.5 size-4 accent-neutral-100">
                                    <span>{{ $label }} <span class="font-mono text-xs text-neutral-500">{{ $key }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('permissions.*')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
        @endif
    </x-ui.card>
</div>
