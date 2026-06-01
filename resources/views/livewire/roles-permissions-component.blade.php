<?php

use Livewire\Volt\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\RolePermissionService;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $rolePermissions = []; // maps role name => array of checked permission names

    public function mount(RolePermissionService $service)
    {
        if (!Auth::check() || !Auth::user()->is_admin)
            abort(403);
        $this->loadData($service);
    }

    public function loadData(RolePermissionService $service)
    {
        $roles = $service->getRoles();
        if ($roles->isEmpty()) {
            $service->seedFromEnums();
            $roles = $service->getRoles();
        }
        foreach ($roles as $role) {
            $this->rolePermissions[$role->name] = $role->permissions->pluck('name')->toArray();
        }
    }

    public function seedSystemDefaults(RolePermissionService $service)
    {
        if (!Auth::user()->is_admin)
            abort(403);

        $service->seedFromEnums();
        $this->loadData($service);
        session()->flash('roles-message', 'Roles and Permissions seeded successfully from system enums!');
    }

    public function togglePermission(RolePermissionService $service, $roleName, $permissionName)
    {
        if (!Auth::user()->is_admin)
            abort(403);

        $currentPermissions = $this->rolePermissions[$roleName] ?? [];
        if (in_array($permissionName, $currentPermissions)) {
            $currentPermissions = array_diff($currentPermissions, [$permissionName]);
        } else {
            $currentPermissions[] = $permissionName;
        }

        $service->syncPermissionsForRole($roleName, $currentPermissions);
        $this->rolePermissions[$roleName] = $currentPermissions;
        session()->flash('roles-message', "Permissions updated for role '{$roleName}'.");
    }

    public function changeUserRole(RolePermissionService $service, $userId, $roleName)
    {
        if (!Auth::user()->is_admin)
            abort(403);

        $user = User::findOrFail($userId);
        if ($user->id === Auth::id()) {
            session()->flash('roles-error', 'You cannot change your own role.');
            return;
        }

        $service->assignRoleToUser($user, $roleName);

        // Sync the is_admin flag in users table for database compatibility
        $user->is_admin = ($roleName === 'admin');
        $user->save();

        session()->flash('roles-message', "Role updated for {$user->name} to '{$roleName}'.");
    }

    public function with(RolePermissionService $service)
    {
        return [
            'roles' => $service->getRoles(),
            'permissions' => $service->getPermissions(),
            'users' => User::orderBy('name', 'asc')->get(),
        ];
    }
};
?>

<div>
    <div class="p-6 w-full mx-auto space-y-8">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            <!-- Header section -->
            <div
                class="bg-slate-900 p-8 text-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold">Roles & Permissions Management</h2>
                    <p class="text-slate-400 mt-1">Configure role permissions and assign user roles</p>
                </div>
                <button wire:click="seedSystemDefaults"
                    class="px-6 py-2.5 bg-gradient-to-r from-[#ed760e] to-[#f4933e] hover:from-[#d56507] hover:to-[#e1812a] text-white rounded-xl font-bold text-sm shadow-md shadow-orange-500/20 transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17" />
                    </svg>
                    Seed System Defaults
                </button>
            </div>

            <!-- Messages section -->
            @if (session()->has('roles-message'))
                <div
                    class="m-8 mb-0 bg-emerald-50 text-emerald-700 p-4 rounded-2xl text-sm border border-emerald-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('roles-message') }}</span>
                </div>
            @endif

            @if (session()->has('roles-error'))
                <div
                    class="m-8 mb-0 bg-rose-50 text-rose-700 p-4 rounded-2xl text-sm border border-rose-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('roles-error') }}</span>
                </div>
            @endif

            <div class="p-8 space-y-8">
                <!-- Roles and Permission Grid -->
                <div>
                    <h3 class="text-lg font-bold text-slate-800 mb-6">Roles & Core Permissions</h3>

                    @if($roles->isEmpty())
                        <div class="text-center py-12 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-400 mx-auto mb-4"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <h4 class="text-sm font-bold text-slate-700">No Roles found</h4>
                            <p class="text-xs text-slate-500 mt-1 mb-4">You must seed system default roles and permissions
                                first.</p>
                            <button wire:click="seedSystemDefaults"
                                class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-bold text-xs">
                                Seed Now
                            </button>
                        </div>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach($roles as $role)
                                <div
                                    class="bg-slate-50 rounded-2xl p-6 border border-slate-100 flex flex-col justify-between shadow-sm">
                                    <div>
                                        <div class="flex justify-between items-center mb-6">
                                            <h4
                                                class="text-md font-extrabold text-slate-800 capitalize flex items-center gap-2">
                                                <span
                                                    class="h-2.5 w-2.5 rounded-full {{ $role->name === 'admin' ? 'bg-indigo-600' : 'bg-emerald-600' }}"></span>
                                                {{ $role->name }}
                                            </h4>
                                            <span
                                                class="px-2.5 py-1 bg-white border border-slate-200 text-slate-500 rounded-lg text-xs font-bold shadow-sm">
                                                {{ count($rolePermissions[$role->name] ?? []) }} Permissions
                                            </span>
                                        </div>

                                        @if($permissions->isEmpty())
                                            <p class="text-xs text-slate-400 italic">No permissions loaded in the database.</p>
                                        @else
                                            <div class="space-y-3.5">
                                                @foreach($permissions as $permission)
                                                    <label
                                                        class="flex items-start gap-3 p-3 bg-white hover:bg-slate-100/50 border border-slate-100 hover:border-slate-200 rounded-xl cursor-pointer transition-all shadow-sm">
                                                        <input type="checkbox"
                                                            wire:click="togglePermission('{{ $role->name }}', '{{ $permission->name }}')"
                                                            @checked(in_array($permission->name, $rolePermissions[$role->name] ?? []))
                                                            class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                                        <div>
                                                            <p class="text-sm font-bold text-slate-800 capitalize">
                                                                {{ str_replace('_', ' ', $permission->name) }}
                                                            </p>
                                                            <p class="text-[11px] text-slate-500 mt-0.5">Allows user with this role to
                                                                access related modules.</p>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Users and Roles Mapping Section -->
                <div class="border-t border-slate-100 pt-8">
                    <h3 class="text-lg font-bold text-slate-800 mb-6">Users & Assigned Roles</h3>
                    <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Current Role</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($users as $user)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 flex items-center gap-3">
                                            <div
                                                class="h-9 w-9 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-sm border border-slate-200/50">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            <span class="font-bold text-slate-900">{{ $user->name }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($user->is_admin)
                                                <span
                                                    class="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] font-black uppercase rounded-lg shadow-sm">Admin</span>
                                            @else
                                                <span
                                                    class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-black uppercase rounded-lg shadow-sm">Member</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @if($user->id !== Auth::id())
                                                <div
                                                    class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 shadow-sm">
                                                    <button wire:click="changeUserRole({{ $user->id }}, 'member')"
                                                        class="px-3 py-1 rounded-md text-xs font-bold transition-all {{ !$user->is_admin ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-950' }}">
                                                        Member
                                                    </button>
                                                    <button wire:click="changeUserRole({{ $user->id }}, 'admin')"
                                                        class="px-3 py-1 rounded-md text-xs font-bold transition-all {{ $user->is_admin ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-950' }}">
                                                        Admin
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400 italic">Self Account</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>