<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    /**
     * Get all tags for a user with optional search.
     *
     * @param int $userId
     * @param string|null $search
     * @return Collection
     */
    public function getTags(int $userId, ?string $search = null): Collection
    {
        // $query = Tag::where('user_id', $userId);
        $query = Tag::query();

        if ($search) {
            $query->where('name', 'like', '%' . trim($search) . '%');
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * Get a specific tag by ID and user ID.
     *
     * @param int $id
     * @param int $userId
     * @return Tag
     */
    public function getTagById(int $id, int $userId): Tag
    {
        return Tag::where('user_id', $userId)->findOrFail($id);
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
     * @param int $userId
     * @param array $data
     * @return Tag
     */
    public function updateTag(int $id, int $userId, array $data): Tag
    {
        $tag = $this->getTagById($id, $userId);
        $tag->update($data);
        return $tag;
    }

    /**
     * Delete a tag.
     *
     * @param int $id
     * @param int $userId
     * @return bool|null
     */
    public function deleteTag(int $id, int $userId): ?bool
    {
        $tag = $this->getTagById($id, $userId);
        return $tag->delete();
    }
}
