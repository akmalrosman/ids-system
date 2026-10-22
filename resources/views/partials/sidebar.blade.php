@php
    use App\Support\Permission;

    // [label, route name, icon, permission, active pattern]
    $sections = [
        'Platform' => [
            ['Dashboard', 'dashboard', 'layout-grid', Permission::DASHBOARD_VIEW, 'dashboard'],
            ['Flights', 'flights.index', 'plane-takeoff', Permission::FLIGHTS_VIEW, 'flights.*'],
            ['Groups / Formations', 'groups.index', 'radar', Permission::GROUPS_VIEW, 'groups.*'],
        ],
        'Settings' => [
            ['Aircrafts', 'aircraft.index', 'plane', Permission::AIRCRAFT_VIEW, 'aircraft.*'],
            ['Aircraft Types', 'aircraft-types.index', 'layers', Permission::AIRCRAFT_VIEW, 'aircraft-types.*'],
            ['Users', 'users.index', 'users', Permission::USERS_MANAGE, 'users.*'],
            ['Roles', 'roles.index', 'id-card', Permission::ROLES_MANAGE, 'roles.*'],
        ],
    ];
    $user = auth()->user();
@endphp

<div class="flex h-full w-64 flex-col p-2">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-md px-2 py-2">
        <span class="flex size-9 items-center justify-center rounded-md bg-neutral-100 text-neutral-900">
            <x-icon name="tower-control" class="size-5" />
        </span>
        <span class="text-sm leading-tight font-semibold">Integrated Debrief<br>System</span>
    </a>

    <nav class="mt-4 flex-1 space-y-5 overflow-y-auto">
        @foreach ($sections as $section => $items)
            @php $visible = array_filter($items, fn ($item) => $user->can($item[3])); @endphp
            @if ($visible)
                <div>
                    <p class="px-2 pb-2 text-xs font-medium text-neutral-400">{{ $section }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($visible as [$label, $route, $icon, $permission, $pattern])
                            @php $active = request()->routeIs($pattern); @endphp
                            <li>
                                <a href="{{ route($route) }}" @class([
                                    'flex items-center gap-2.5 rounded-md px-2 py-2 text-sm transition-colors',
                                    'bg-neutral-800/80 font-medium text-neutral-50' => $active,
                                    'text-neutral-300 hover:bg-neutral-900 hover:text-neutral-50' => ! $active,
                                ])>
                                    <x-icon :name="$icon" class="size-4" />
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </nav>

    {{-- User menu --}}
    <div x-data="{ open: false }" class="relative" @click.outside="open = false">
        <button type="button" @click="open = !open" class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left hover:bg-neutral-900">
            <span class="flex size-8 items-center justify-center rounded-md bg-neutral-800 text-xs font-medium">{{ $user->initials() }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium">{{ $user->name }}</span>
                <span class="block truncate text-xs text-neutral-500">{{ $user->role?->name ?? 'No role' }}</span>
            </span>
            <x-icon name="chevrons-up-down" class="size-4 text-neutral-400" />
        </button>

        <div x-cloak x-show="open" x-transition
             class="absolute bottom-full left-0 mb-2 w-full rounded-lg border border-neutral-800 bg-neutral-950 p-1 shadow-xl">
            <div class="px-2 py-1.5 text-xs text-neutral-500">{{ $user->email }}</div>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-neutral-900">
                <x-icon name="settings" class="size-4" /> Settings
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-neutral-900">
                    <x-icon name="log-out" class="size-4" /> Log out
                </button>
            </form>
        </div>
    </div>
</div>
