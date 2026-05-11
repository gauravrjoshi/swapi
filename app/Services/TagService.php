<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    /**
     * Get all tags with optional search.
     *
     * @param string|null $search
     * @return Collection
     */
    public function getTags(?string $search = null): Collection
    {
        $query = Tag::query();

        if ($search) {
            $query->where('name', 'like', '%' . trim($search) . '%');
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * Get a specific tag by ID.
     *
     * @param int $id
     * @return Tag
     */
    public function getTagById(int $id): Tag
    {
        return Tag::findOrFail($id);
    }

    /**
     * Create a new tag.
     *
     * @param array $data
     * @return Tag
     */
    public function createTag(array $data): Tag
    {
        $data['user_id'] = $data['user_id'] ?? Auth::id();
        return Tag::create($data);
    }

    /**
     * Update an existing tag.
     *
     * @param int $id
     * @param array $data
     * @return Tag
     */
    public function updateTag(int $id, array $data): Tag
    {
        $tag = $this->getTagById($id);
        $tag->update($data);
        return $tag;
    }

    /**
     * Delete a tag.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteTag(int $id): ?bool
    {
        $tag = $this->getTagById($id);
        return $tag->delete();
    }
}
