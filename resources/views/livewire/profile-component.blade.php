<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $photo;

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile()
    {
        $user = Auth::user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'photo' => 'nullable|image|max:1024', // 1MB Max
        ];

        if (!empty($this->password)) {
            $rules['password'] = 'required|min:8|confirmed';
        }

        $this->validate($rules);

        if ($this->photo) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            
            $path = $this->photo->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        $user->name = $this->name;
        $user->email = $this->email;

        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }

        $user->save();

        $this->reset(['password', 'password_confirmation', 'photo']);
        session()->flash('profile-message', 'Profile updated successfully.');
        
        // Dispatch event to refresh other components (like navbar) if they depend on this
        $this->dispatch('profile-updated');
    }
};
?>

<div class="p-6 w-full mx-auto space-y-8">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="p-8 text-white" style="background: linear-gradient(to bottom right, #4f46e5, #3730a3);">
            <div>
                <h2 class="text-3xl font-black" style="color: white; margin: 0;">{{ $name }}</h2>
                <p class="mt-1" style="color: #e0e7ff; margin: 0; font-weight: 700;">{{ Auth::user()->is_admin ? 'Administrator' : 'Standard User' }}</p>
            </div>
        </div>

        <div class="p-8 space-y-8">
            <form wire:submit="updateProfile" class="space-y-6">
                @if (session()->has('profile-message'))
                    <div class="bg-emerald-50 text-emerald-700 p-4 rounded-2xl text-sm border border-emerald-100">
                        {{ session('profile-message') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-1">Full Name</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-medium">
                        @error('name') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-1">Email Address</label>
                        <input type="email" wire:model="email" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-medium">
                        @error('email') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Profile Picture Upload -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-800">Profile Picture</h3>
                    <p class="text-sm text-slate-500">Upload a photo to personalize your account. Max size: 1MB.</p>
                    
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <!-- Current / Preview -->
                        <div style="height: 80px; width: 80px; border-radius: 9999px; overflow: hidden; background-color: #e8eaed; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 3px solid #e2e8f0;">
                            @if($photo)
                                <img src="{{ $photo->temporaryUrl() }}" style="height: 100%; width: 100%; object-fit: cover;">
                            @elseif(Auth::user()->profile_photo_path)
                                <img src="{{ Storage::url(Auth::user()->profile_photo_path) }}" style="height: 100%; width: 100%; object-fit: cover;">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" style="height: 36px; width: 36px; color: #94a3b8;" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label for="photo-field" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background-color: #f1f5f9; color: #334155; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; border: 1px dashed #cbd5e1; transition: all 0.2s;"
                                onmouseover="this.style.backgroundColor='#e2e8f0'" onmouseout="this.style.backgroundColor='#f1f5f9'">
                                <svg xmlns="http://www.w3.org/2000/svg" style="height: 18px; width: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Choose Photo
                                <input type="file" id="photo-field" wire:model="photo" style="display: none;" accept="image/*">
                            </label>

                            <div wire:loading wire:target="photo" style="font-size: 12px; font-weight: 700; color: #6366f1;">
                                Uploading...
                            </div>
                            @error('photo') <span style="font-size: 12px; color: #e11d48;">{{ $message }}</span> @enderror

                            @if($photo)
                                <span style="font-size: 12px; color: #16a34a; font-weight: 600;">✓ New photo selected. Click "Save Changes" to apply.</span>
                            @elseif(Auth::user()->profile_photo_path)
                                <span style="font-size: 12px; color: #64748b;">Current photo uploaded.</span>
                            @else
                                <span style="font-size: 12px; color: #94a3b8;">No photo uploaded yet.</span>
                            @endif
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-800">Change Password</h3>
                    <p class="text-sm text-slate-500">Leave these blank if you don't want to change your password.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">New Password</label>
                            <input type="password" wire:model="password" autocomplete="new-password" placeholder="Leave blank to keep current" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                            @error('password') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Confirm Password</label>
                            <input type="password" wire:model="password_confirmation" autocomplete="new-password" placeholder="Re-enter new password" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
