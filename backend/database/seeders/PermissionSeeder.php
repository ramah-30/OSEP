<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The granular permission layer that sits on top of the roles seeded in
 * RoleSeeder. Permissions are grouped by module; each role is granted the set
 * that matches its responsibilities in the Phase 2 spec. Adding a capability
 * later is a row here plus a name in the relevant role's grant list.
 */
class PermissionSeeder extends Seeder
{
    /**
     * name => [module, label]
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private array $permissions = [
        // Events
        'create_event' => ['events', 'Create events'],
        'manage_events' => ['events', 'Manage events'],
        'view_events' => ['events', 'View events'],
        // Clients
        'manage_clients' => ['clients', 'Manage clients'],
        // Vendors (planner coordinating vendors)
        'manage_vendors' => ['vendors', 'Coordinate vendors'],
        // Tasks & schedule
        'manage_tasks' => ['tasks', 'Manage tasks'],
        'manage_calendar' => ['calendar', 'Manage the calendar'],
        // Budget
        'manage_budget' => ['budget', 'Manage budgets'],
        'view_budget' => ['budget', 'View budget'],
        // Documents
        'manage_documents' => ['documents', 'Manage documents'],
        'view_documents' => ['documents', 'View documents'],
        // Proposals & approvals
        'send_proposals' => ['approvals', 'Send proposals'],
        'respond_approvals' => ['approvals', 'Respond to approvals'],
        // Messaging
        'send_messages' => ['messages', 'Send messages'],
        // Reports & analytics
        'view_reports' => ['reports', 'View reports'],
        // Payments
        'make_payments' => ['payments', 'Make payments'],
        'view_payments' => ['payments', 'View payments'],
        // Vendor business
        'manage_business_profile' => ['vendor_business', 'Manage business profile'],
        'manage_services' => ['vendor_business', 'Manage services'],
        'manage_portfolio' => ['vendor_business', 'Manage portfolio'],
        'manage_availability' => ['vendor_business', 'Manage availability'],
        'respond_requests' => ['vendor_business', 'Respond to booking requests'],
        'submit_quotations' => ['vendor_business', 'Submit quotations'],
        // Platform administration
        'manage_users' => ['admin', 'Manage users'],
        'manage_platform' => ['admin', 'Manage the platform'],
    ];

    /**
     * role name => granted permission names.
     *
     * @var array<string, array<int, string>>
     */
    private array $grants = [
        'event_planner' => [
            'create_event', 'manage_events', 'view_events', 'manage_clients', 'manage_vendors',
            'manage_tasks', 'manage_calendar', 'manage_budget', 'view_budget', 'manage_documents',
            'view_documents', 'send_proposals', 'send_messages', 'view_reports', 'view_payments',
        ],
        'client' => [
            'view_events', 'view_budget', 'view_documents', 'respond_approvals', 'send_messages',
            'make_payments', 'view_payments',
        ],
        'vendor' => [
            'manage_business_profile', 'manage_services', 'manage_portfolio', 'manage_availability',
            'respond_requests', 'submit_quotations', 'send_messages', 'view_payments',
        ],
        'staff' => [
            'view_events', 'view_reports', 'manage_users',
        ],
        // admin gets everything (resolved below).
    ];

    public function run(): void
    {
        foreach ($this->permissions as $name => [$module, $label]) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['module' => $module, 'label' => $label],
            );
        }

        $all = Permission::pluck('id', 'name');

        foreach ($this->grants as $roleName => $names) {
            $this->sync($roleName, $all->only($names)->values()->all());
        }

        // Administrators hold every permission.
        $this->sync('admin', $all->values()->all());
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    private function sync(string $roleName, array $permissionIds): void
    {
        $role = Role::where('name', $roleName)->first();

        $role?->permissions()->sync($permissionIds);
    }
}
