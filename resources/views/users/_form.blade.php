@csrf
<x-ui.card class="max-w-2xl">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field label="Name" name="name" required>
            <input id="name" name="name" required maxlength="100" class="form-control" value="{{ old('name', $user->name) }}">
        </x-ui.field>
        <x-ui.field label="Role" name="role_id" required>
            <select id="role_id" name="role_id" required class="form-control">
                <option value="">Select role…</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </x-ui.field>
        <x-ui.field label="Email" name="email" required>
            <input id="email" name="email" type="email" required class="form-control" value="{{ old('email', $user->email) }}">
        </x-ui.field>
        <x-ui.field label="Phone" name="phone">
            <input id="phone" name="phone" maxlength="30" class="form-control" value="{{ old('phone', $user->phone) }}">
        </x-ui.field>
        <x-ui.field label="Password" name="password" :required="! $user->exists"
                    :hint="$user->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters.'">
            <input id="password" name="password" type="password" autocomplete="new-password" class="form-control" @required(! $user->exists)>
        </x-ui.field>
        <x-ui.field label="Confirm password" name="password_confirmation">
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control">
        </x-ui.field>
    </div>
</x-ui.card>
