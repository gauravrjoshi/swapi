<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $name;
    public $email;
    public $password;

    public $editingUserId = null;
    public $editName;
    public $editEmail;
    public $editPassword;
    public $editIsAdmin;

    public $confirmingDeletionId = null;

    public function createUser()
    {
        if (!Auth::user()->is_admin) abort(403);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        
        User::create($validated);

        $this->reset(['name', 'email', 'password']);
        session()->flash('user-message', 'User added successfully.');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        if (!Auth::user()->is_admin) abort(403);

        $this->editingUserId = $id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editIsAdmin = $user->is_admin;
        $this->editPassword = '';
    }

    public function updateUser()
    {
        if (!Auth::user()->is_admin) abort(403);
        $user = User::findOrFail($this->editingUserId);

        $rules = [
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|unique:users,email,' . $user->id,
        ];

        if ($this->editPassword) {
            $rules['editPassword'] = 'min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->editName,
            'email' => $this->editEmail,
            'is_admin' => $this->editIsAdmin,
        ];

        if ($this->editPassword) {
            $data['password'] = Hash::make($this->editPassword);
        }

        $user->update($data);

        $this->cancelEdit();
        session()->flash('user-message', 'User updated successfully.');
    }

    public function confirmDelete($id)
    {
        if ($id == Auth::id()) {
            session()->flash('user-error', 'You cannot delete your own account.');
            return;
        }
        $this->confirmingDeletionId = $id;
    }

    public function deleteUser()
    {
        if (!Auth::user()->is_admin) abort(403);
        
        $user = User::findOrFail($this->confirmingDeletionId);
        $user->delete();

        $this->confirmingDeletionId = null;
        session()->flash('user-message', 'User deleted successfully.');
    }

    public function cancelEdit()
    {
        $this->reset(['editingUserId', 'editName', 'editEmail', 'editPassword', 'editIsAdmin']);
    }

    public function cancelDelete()
    {
        $this->confirmingDeletionId = null;
    }

    public function with()
    {
        return [
            'users' => User::latest()->get(),
        ];
    }
};
?>

<div>
    <div class="p-6 w-full mx-auto space-y-8">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="bg-slate-900 p-8 text-white flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold">User Management</h2>
                    <p class="text-slate-400 mt-1">Add and manage users who can access the system</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                <!-- Form Area -->
                <div class="p-8 space-y-6 bg-slate-50">
                    @if($editingUserId)
                        <h3 class="text-lg font-bold text-slate-800">Edit User</h3>
                        <form wire:submit="updateUser" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Full Name</label>
                                <input type="text" wire:model="editName" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-900 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Email Address</label>
                                <input type="email" wire:model="editEmail" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-900 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">New Password (Optional)</label>
                                <input type="password" wire:model="editPassword" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-900 transition-all" placeholder="Leave blank to keep same">
                            </div>
                            <div class="flex items-center gap-2 px-1">
                                <input type="checkbox" wire:model="editIsAdmin" id="editIsAdmin" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                                <label for="editIsAdmin" class="text-sm font-bold text-slate-700">Administrator Access</label>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 py-3 bg-slate-900 text-white rounded-xl font-bold hover:bg-slate-800 transition-all">
                                    Update
                                </button>
                                <button type="button" wire:click="cancelEdit" class="px-4 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-100 transition-all">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @else
                        <h3 class="text-lg font-bold text-slate-800">Add New User</h3>
                        <form wire:submit="createUser" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Full Name</label>
                                <input type="text" wire:model="name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-900 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Email Address</label>
                                <input type="email" wire:model="email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-900 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Password</label>
                                <input type="password" wire:model="password" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-900 transition-all">
                            </div>
                            <button type="submit" class="w-full py-3 bg-slate-900 text-white rounded-xl font-bold hover:bg-slate-800 transition-all">
                                Create User
                            </button>
                        </form>
                    @endif

                    @if (session()->has('user-message'))
                        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-2xl text-sm border border-emerald-100">
                            {{ session('user-message') }}
                        </div>
                    @endif

                    @if (session()->has('user-error'))
                        <div class="bg-rose-50 text-rose-700 p-4 rounded-2xl text-sm border border-rose-100">
                            {{ session('user-error') }}
                        </div>
                    @endif
                </div>

                <!-- User List -->
                <div class="p-8 md:col-span-2">
                    <h3 class="text-lg font-bold text-slate-800 mb-6">Existing Users</h3>
                    <div class="space-y-3">
                        @foreach($users as $user)
                            <div class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl hover:border-slate-200 transition-all shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-600">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                            @if($user->is_admin)
                                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase rounded-lg">Admin</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="editUser({{ $user->id }})" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    @if($user->id !== Auth::id())
                                        <button wire:click="confirmDelete({{ $user->id }})" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 000-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teleported Delete Confirmation Modal -->
    @if($confirmingDeletionId)
        @teleport('body')
            <div class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-center; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);">
                <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 space-y-6" style="background-color: white; border-radius: 1.5rem; width: 100%; max-width: 24rem; padding: 2rem;">
                    <div class="h-20 w-20 flex items-center justify-center mx-auto rounded-full" style="background-color: #fff1f2; height: 5rem; width: 5rem; margin: 0 auto; display: flex; align-items: center; justify-center; border-radius: 9999px;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" style="color: #e11d48; height: 2.5rem; width: 2.5rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="text-center" style="text-align: center;">
                        <h3 class="text-2xl font-black text-slate-900" style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">Delete User?</h3>
                        <p class="text-slate-500 mt-2 leading-relaxed" style="color: #64748b; margin-top: 0.5rem; line-height: 1.625;">This user will lose all access immediately. All their recorded transactions will remain but will be linked to a 'Deleted User'.</p>
                    </div>
                    <div class="flex gap-3 pt-2" style="display: flex; gap: 0.75rem; padding-top: 0.5rem;">
                        <button wire:click="cancelDelete" class="flex-1 py-3.5 bg-slate-100 text-slate-700 rounded-2xl font-bold" style="flex: 1; padding: 0.875rem; background-color: #f1f5f9; color: #334155; border-radius: 1rem; border: none; cursor: pointer;">
                            Cancel
                        </button>
                        <button wire:click="deleteUser" class="flex-1 py-3.5 text-white rounded-2xl font-bold" style="flex: 1; padding: 0.875rem; background-color: #e11d48; color: white; border-radius: 1rem; border: none; cursor: pointer;">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>