{{-- The system runs offline (no email server), so password resets are done by an administrator. --}}
<x-layouts.guest title="Forgot password">
    <div class="flex min-h-full items-center justify-center px-6">
        <div class="w-full max-w-sm text-center">
            <x-icon name="tower-control" class="mx-auto mb-4 size-10" />
            <h1 class="text-xl font-medium">Forgot your password?</h1>
            <p class="mt-3 text-sm text-neutral-400">
                Please contact your system administrator. An administrator can set a new password for you from
                <span class="text-neutral-200">Settings → Users</span>.
            </p>
            <a href="{{ route('login') }}" class="mt-6 inline-block text-sm underline underline-offset-4">Back to log in</a>
        </div>
    </div>
</x-layouts.guest>
