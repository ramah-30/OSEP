<?php

namespace App\Enums;

/**
 * The identity a user selects when registering.
 *
 * Every case maps 1:1 onto a seeded role of the same name, so a new account
 * type only ever needs a case here plus a row in RoleSeeder — no schema change.
 */
enum AccountType: string
{
    case EventPlanner = 'event_planner';
    case Vendor = 'vendor';
    case Client = 'client';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::EventPlanner => 'Event Planner',
            self::Vendor => 'Vendor',
            self::Client => 'Client',
            self::Admin => 'Administrator',
        };
    }

    /**
     * Where the frontend sends this account type after signing in.
     */
    public function dashboardPath(): string
    {
        return match ($this) {
            self::EventPlanner => '/dashboard/planner',
            self::Vendor => '/dashboard/vendor',
            self::Client => '/dashboard/client',
            self::Admin => '/dashboard/admin',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
