<x-layouts.guest title="Log in">
    <div class="flex min-h-full items-center justify-center px-6">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center text-center">
                <x-icon name="tower-control" class="mb-4 size-10" />
                <h1 class="text-xl font-medium">Log in to your account</h1>
                <p class="mt-2 text-sm text-neutral-400">Enter your email and password below to log in</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <x-ui.field label="Email address" name="email">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                           placeholder="email@example.com" class="form-control h-10">
                </x-ui.field>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="password" class="text-sm font-medium text-neutral-200">Password</label>
                        <a href="{{ route('password.request') }}" class="text-sm text-neutral-300 underline underline-offset-4 hover:text-neutral-100">Forgot password?</a>
                    </div>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           placeholder="Password" class="form-control h-10">
                    @error('password')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                           class="size-4 rounded border-neutral-700 bg-neutral-900 accent-neutral-100">
                    Remember me
                </label>

                <x-ui.button type="submit" class="h-10 w-full">Log in</x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.guest>
