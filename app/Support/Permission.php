<?php

namespace App\Support;

/**
 * All permission keys used by the app.
 *
 * Each key is registered as a Gate in AppServiceProvider, so you can use
 * `@can('flights.manage')`, `$user->can('flights.manage')` or the
 * `can:flights.manage` route middleware. The "super-admin" role has all of them.
 */
final class Permission
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const FLIGHTS_VIEW = 'flights.view';

    public const FLIGHTS_MANAGE = 'flights.manage';

    public const GROUPS_VIEW = 'groups.view';

    public const GROUPS_MANAGE = 'groups.manage';

    public const AIRCRAFT_VIEW = 'aircraft.view';

    public const AIRCRAFT_MANAGE = 'aircraft.manage';

    public const USERS_MANAGE = 'users.manage';

    public const ROLES_MANAGE = 'roles.manage';

    /**
     * Permission keys with a human label, grouped for the role form.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'Platform' => [
                self::DASHBOARD_VIEW => 'View dashboard',
                self::FLIGHTS_VIEW => 'View & present flights',
                self::FLIGHTS_MANAGE => 'Create / edit / delete flights, upload FDR & BVR',
                self::GROUPS_VIEW => 'View & present groups / formations',
                self::GROUPS_MANAGE => 'Create / edit / delete groups / formations',
            ],
            'Settings' => [
                self::AIRCRAFT_VIEW => 'View aircraft & aircraft types',
                self::AIRCRAFT_MANAGE => 'Manage aircraft & aircraft types',
                self::USERS_MANAGE => 'Manage users',
                self::ROLES_MANAGE => 'Manage roles',
            ],
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(array_merge(...array_values(self::grouped())));
    }
}
