<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Roles are seeded up-front - including the ones nothing assigns yet.
     * Granting a user `admin` or `staff` later is an insert into user_roles,
     * never a schema change.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'event_planner',
                'label' => 'Event Planner',
                'description' => 'Professional planner managing weddings, conferences, corporate events, birthdays and exhibitions.',
            ],
            [
                'name' => 'vendor',
                'label' => 'Vendor',
                'description' => 'Business supplying event services such as catering, venues, music, decor, photography, florals, security or transport.',
            ],
            [
                'name' => 'client',
                'label' => 'Client',
                'description' => 'Individual or company organising an event.',
            ],
            [
                'name' => 'admin',
                'label' => 'Administrator',
                'description' => 'Full platform administration access.',
            ],
            [
                'name' => 'staff',
                'label' => 'Staff',
                'description' => 'Internal support team member with limited administrative access.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
