<?php

use Livewire\Volt\Component;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $name = '';
    public $color = '#6366f1';

    public $search = '';

    public $editingTagId = null;
    public $editName = '';
    public $editColor = '#6366f1';

    public function createTag()
    {
        $this->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tags', 'name')->where(function ($query) {
                    $query->where('user_id', Auth::id());
                }),
            ],
            'color' => 'required|string|max:7',
        ]);

        Tag::create([
            'name' => $this->name,
            'color' => $this->color,
            'user_id' => Auth::id(),
        ]);

        $this->reset(['name']);
        $this->color = '#6366f1';
        session()->flash('tag-message', 'Tag created successfully.');
    }

    public function editTag($id)
    {
        $tag = Tag::where('user_id', Auth::id())->findOrFail($id);

        $this->editingTagId = $id;
        $this->editName = $tag->name;
        $this->editColor = $tag->color;
    }

    public function updateTag()
    {
        $tag = Tag::where('user_id', Auth::id())->findOrFail($this->editingTagId);

        $this->validate([
            'editName' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tags', 'name')->ignore($this->editingTagId)->where(function ($query) {
                    $query->where('user_id', Auth::id());
                }),
            ],
            'editColor' => 'required|string|max:7',
        ]);

        $tag->update([
            'name' => $this->editName,
            'color' => $this->editColor,
        ]);

        $this->cancelEdit();
        session()->flash('tag-message', 'Tag updated successfully.');
    }

    public function deleteTag($id)
    {
        $tag = Tag::where('user_id', Auth::id())->findOrFail($id);
        $tag->delete();
        session()->flash('tag-message', 'Tag deleted successfully.');
    }

    public function cancelEdit()
    {
        $this->reset(['editingTagId', 'editName', 'editColor']);
    }

    public function with()
    {
        $query = Tag::where('user_id', Auth::id());

        if ($this->search) {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        }

        return [
            'tags' => $query->latest()->get(),
        ];
    }
};
?>

<div class="p-6 w-full mx-auto space-y-8">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="p-8 text-white" style="background: linear-gradient(to bottom right, #7c3aed, #4f46e5);">
            <h2 class="text-2xl font-bold">Manage Tags</h2>
            <p style="color: #c4b5fd;" class="mt-1">Create color-coded tags to categorize your transactions</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100">
            <!-- Form -->
            <div class="p-8 space-y-6 bg-slate-50">
                @if($editingTagId)
                    <h3 class="text-lg font-bold text-slate-800">Edit Tag</h3>
                    <form wire:submit="updateTag" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Tag Name</label>
                            <input type="text" wire:model="editName" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="e.g. Food, Rent">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Color</label>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <input type="color" wire:model="editColor" style="height: 42px; width: 42px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; padding: 2px;">
                                <input type="text" wire:model="editColor" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-mono text-sm" placeholder="#6366f1">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all">
                                Update
                            </button>
                            <button type="button" wire:click="cancelEdit" class="px-4 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-100 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>
                @else
                    <h3 class="text-lg font-bold text-slate-800">Create Tag</h3>
                    <form wire:submit="createTag" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Tag Name</label>
                            <input type="text" wire:model="name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="e.g. Food, Rent, Salary">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Color</label>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <input type="color" wire:model="color" style="height: 42px; width: 42px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; padding: 2px;">
                                <input type="text" wire:model="color" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-mono text-sm" placeholder="#6366f1">
                            </div>
                        </div>
                        <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                            Create Tag
                        </button>
                    </form>
                @endif

                @if (session()->has('tag-message'))
                    <div class="bg-emerald-50 text-emerald-700 p-4 rounded-2xl text-sm border border-emerald-100">
                        {{ session('tag-message') }}
                    </div>
                @endif
            </div>

            <!-- Tags List -->
            <div class="p-8 md:col-span-2">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <h3 class="text-lg font-bold text-slate-800">Your Tags</h3>
                    
                    <div class="relative w-full sm:w-64">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tags..." 
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                @if($tags->isEmpty())
                    <div class="bg-slate-50 p-8 rounded-2xl border border-dashed border-slate-200 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" style="height: 40px; width: 40px; color: #94a3b8; margin: 0 auto 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <p class="text-slate-500 font-medium">No tags yet. Create your first tag!</p>
                    </div>
                @else
                    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                        @foreach($tags as $tag)
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; background-color: white; border: 1px solid #e2e8f0; border-radius: 16px; transition: all 0.2s;" class="hover:shadow-md group">
                                <div style="height: 14px; width: 14px; border-radius: 9999px; background-color: {{ $tag->color }}; flex-shrink: 0;"></div>
                                <span style="font-size: 14px; font-weight: 600; color: #1e293b;">{{ $tag->name }}</span>
                                
                                <div style="display: flex; gap: 4px; margin-left: 4px;">
                                    <button wire:click="editTag({{ $tag->id }})" style="padding: 4px; color: #94a3b8; background: none; border: none; cursor: pointer; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.color='#6366f1'; this.style.backgroundColor='#eef2ff'" onmouseout="this.style.color='#94a3b8'; this.style.backgroundColor='transparent'">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="height: 14px; width: 14px;" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    <button wire:click="deleteTag({{ $tag->id }})" wire:confirm="Delete this tag?" style="padding: 4px; color: #94a3b8; background: none; border: none; cursor: pointer; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.color='#e11d48'; this.style.backgroundColor='#fff1f2'" onmouseout="this.style.color='#94a3b8'; this.style.backgroundColor='transparent'">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="height: 14px; width: 14px;" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
