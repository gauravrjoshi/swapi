<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\RoleEnums;
use App\Enums\PermissionEmnum;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private RolePermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RolePermissionService();
    }

    /**
     * Test seeding roles and permissions from enums.
     */
    public function test_can_seed_roles_and_permissions_from_enums()
    {
        // Act
        $this->service->seedFromEnums();

        // Assert permissions were seeded
        foreach (PermissionEmnum::cases() as $case) {
            $this->assertDatabaseHas('permissions', [
                'name' => $case->value,
                'guard_name' => 'web',
            ]);
        }

        // Assert roles were seeded
        foreach (RoleEnums::cases() as $case) {
            $this->assertDatabaseHas('roles', [
                'name' => $case->value,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Test synchronizing permissions for a role.
     */
    public function test_can_sync_permissions_for_a_role()
    {
        // Seed first
        $this->service->seedFromEnums();

        $role = RoleEnums::ADMIN->value;
        $permissions = [
            PermissionEmnum::VIEW_DASHBOARD->value,
            PermissionEmnum::MANAGE_TRANSACTIONS->value,
        ];

        // Act
        $updatedRole = $this->service->syncPermissionsForRole($role, $permissions);

        // Assert
        $this->assertTrue($updatedRole->hasPermissionTo(PermissionEmnum::VIEW_DASHBOARD->value));
        $this->assertTrue($updatedRole->hasPermissionTo(PermissionEmnum::MANAGE_TRANSACTIONS->value));
        $this->assertFalse($updatedRole->hasPermissionTo(PermissionEmnum::MANAGE_ACCOUNTS->value));
    }

    /**
     * Test assigning a role to a user.
     */
    public function test_can_assign_role_to_user()
    {
        // Seed first
        $this->service->seedFromEnums();

        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        // Act
        $this->service->assignRoleToUser($user, RoleEnums::ADMIN->value);

        // Assert
        $this->assertTrue($user->hasRole(RoleEnums::ADMIN->value));
    }

    /**
     * Test self-healing role creation during assignment.
     */
    public function test_can_assign_unseeded_role_to_user_due_to_self_healing()
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        // Act - assign role that has not been seeded in DB yet
        $this->service->assignRoleToUser($user, 'new-role');

        // Assert
        $this->assertTrue($user->hasRole('new-role'));
        $this->assertDatabaseHas('roles', [
            'name' => 'new-role',
            'guard_name' => 'web',
        ]);
    }

    /**
     * Test self-healing role and permission creation during synchronization.
     */
    public function test_can_sync_unseeded_permissions_due_to_self_healing()
    {
        // Act - sync unseeded permissions for an unseeded role
        $updatedRole = $this->service->syncPermissionsForRole('dynamic-role', ['dynamic-permission-1', 'dynamic-permission-2']);

        // Assert
        $this->assertDatabaseHas('roles', [
            'name' => 'dynamic-role',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'dynamic-permission-1',
            'guard_name' => 'web',
        ]);
        $this->assertTrue($updatedRole->hasPermissionTo('dynamic-permission-1'));
        $this->assertTrue($updatedRole->hasPermissionTo('dynamic-permission-2'));
    }

    /**
     * Test role management route access control.
     */
    public function test_roles_route_cannot_be_accessed_by_guests()
    {
        $response = $this->get('/admin/roles');
        $response->assertRedirect('/login');
    }

    public function test_roles_route_cannot_be_accessed_by_non_admin_users()
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin/roles');
        $response->assertStatus(403);
    }

    public function test_roles_route_can_be_accessed_by_admin_users()
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin/roles');
        $response->assertStatus(200);
    }
}
