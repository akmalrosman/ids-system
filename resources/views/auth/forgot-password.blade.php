{{-- The system runs offline (no email server), so password resets are done by an administrator. --}}
<x-layouts.auth title="Forgot password">
    <div class="text-center">
        <h1 class="text-xl font-semibold tracking-tight">Forgot your password?</h1>
        <p class="mt-3 text-sm text-neutral-400">
            Please contact your system administrator. They can set a new password for you in
            <span class="text-neutral-200">Settings → Users</span>.
        </p>
    </div>

    <x-ui.button variant="outline" :href="route('login')" class="mt-8 h-10 w-full">Back to log in</x-ui.button>
</x-layouts.auth>
