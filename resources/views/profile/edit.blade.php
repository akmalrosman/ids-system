<x-layouts.app title="Settings" :breadcrumbs="['Settings' => null]">
    <x-ui.page-header title="Settings" description="Update your profile and password." />

    <div class="grid max-w-4xl gap-6 lg:grid-cols-2">
        <x-ui.card title="Profile">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf @method('PUT')
                <x-ui.field label="Name" name="name" required>
                    <input id="name" name="name" required class="form-control" value="{{ old('name', $user->name) }}">
                </x-ui.field>
                <x-ui.field label="Email" name="email" required>
                    <input id="email" name="email" type="email" required class="form-control" value="{{ old('email', $user->email) }}">
                </x-ui.field>
                <x-ui.field label="Phone" name="phone">
                    <input id="phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                </x-ui.field>
                <x-ui.button type="submit">Save</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Password">
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-5">
                @csrf @method('PUT')
                @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
                    <div class="space-y-2">
                        <label for="{{ $field }}" class="block text-sm font-medium text-neutral-200">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" class="form-control">
                        @error($field, 'password')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <x-ui.button type="submit">Change password</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
