{{-- The system runs offline (no email server), so password resets are done by an administrator. --}}
<x-layouts.auth title="Forgot password">
    <h1 class="text-2xl font-semibold tracking-tight">Forgot your password?</h1>
    <p class="mt-3 text-sm text-neutral-400">
        This system runs offline, so passwords can't be reset by email. Please contact your system administrator.
        They can set a new password for you in <span class="text-neutral-200">Settings → Users</span>.
    </p>

    <x-ui.button variant="outline" :href="route('login')" class="mt-8 w-full">
        Back to log in
    </x-ui.button>
</x-layouts.auth>
