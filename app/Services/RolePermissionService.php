<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Enums\RoleEnums;
use App\Enums\PermissionEmnum;
use Illuminate\Support\Collection;

class RolePermissionService
{
    /**
     * Get all roles.
     *
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return Role::with('permissions')->get();
    }

    /**
     * Get all permissions.
     *
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return Permission::all();
    }

    public function seedFromEnums(): void
    {
        // 1. Seed Permissions
        $allPermissions = [];
        foreach (PermissionEmnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
            $allPermissions[] = $permission->value;
        }

        // 2. Seed Roles and assign all permissions to Admin
        foreach (RoleEnums::cases() as $role) {
            $roleModel = Role::findOrCreate($role->value, 'web');
            if ($role->value === RoleEnums::ADMIN->value) {
                $roleModel->syncPermissions($allPermissions);
            }
        }

        // 3. Self-healing: sync all existing users to their Spatie roles
        foreach (User::all() as $user) {
            $targetRole = $user->is_admin ? RoleEnums::ADMIN->value : RoleEnums::MEMBER->value;
            if (!$user->hasRole($targetRole, 'web')) {
                $user->assignRole($targetRole);
            }
        }
    }

    /**
     * Synchronize permissions of a role.
     *
     * @param int|string $roleIdOrName
     * @param array $permissionNames
     * @return Role
     */
    public function syncPermissionsForRole($roleIdOrName, array $permissionNames): Role
    {
        $role = is_numeric($roleIdOrName)
            ? Role::findById($roleIdOrName)
            : Role::findOrCreate($roleIdOrName, 'web');

        // Self-healing: make sure each synchronized permission exists in DB
        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $role->syncPermissions($permissionNames);
        return $role;
    }

    /**
     * Assign a role to a user.
     *
     * @param User $user
     * @param string $roleName
     * @return User
     */
    public function assignRoleToUser(User $user, string $roleName): User
    {
        // Self-healing: make sure the role exists in DB before assigning
        Role::findOrCreate($roleName, 'web');

        $user->syncRoles([$roleName]);
        return $user;
    }

    /**
     * Remove a role from a user.
     *
     * @param User $user
     * @param string $roleName
     * @return User
     */
    public function removeRoleFromUser(User $user, string $roleName): User
    {
        // Self-healing: make sure the role exists in DB before removing
        Role::findOrCreate($roleName, 'web');

        $user->removeRole($roleName);
        return $user;
    }
}
