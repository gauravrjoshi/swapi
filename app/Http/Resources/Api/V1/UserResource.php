<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->roles;
        if ($roles->isEmpty()) {
            $targetRoleName = $this->is_admin ? 'admin' : 'member';
            \Spatie\Permission\Models\Role::findOrCreate($targetRoleName, 'web');
            $this->assignRole($targetRoleName);
            $roles = $this->roles()->get();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'unid' => $this->unid,
            'is_admin' => (bool) $this->is_admin,
            'roles' => $roles->pluck('name'),
            'permissions' => $roles->flatMap(fn($role) => $role->permissions)
                ->concat($this->permissions)
                ->pluck('name')
                ->unique()
                ->values(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
