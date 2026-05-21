<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

class TagService
{
    /**
     * System default tags.
     */
    private static array $defaultTags = [
        ['id' => -1, 'name' => 'Grocery', 'color' => '#f87171'],
        ['id' => -2, 'name' => 'Shopping', 'color' => '#fb923c'],
        ['id' => -3, 'name' => 'Salary', 'color' => '#fbbf24'],
        ['id' => -4, 'name' => 'Savings', 'color' => '#34d399'],
        ['id' => -5, 'name' => 'Medical', 'color' => '#60a5fa'],
    ];

    /**
     * Get all default tags as virtual models.
     *
     * @param int|null $userId
     * @return Collection
     */
    public function getDefaultTags(?int $userId = null): Collection
    {
        $collection = new Collection();
        $targetUserId = $userId ?? Auth::id() ?? 0;
        $now = now()->toDateTimeString();
        foreach (self::$defaultTags as $tagData) {
            $tag = new Tag();
            $tagData['user_id'] = $targetUserId;
            $tagData['created_at'] = $now;
            $tagData['updated_at'] = $now;
            $tag->forceFill($tagData);
            $tag->exists = true;
            $collection->push($tag);
        }
        return $collection;
    }

    /**
     * Get all tags for a user with optional search.
     *
     * @param int $userId
     * @param string|null $search
     * @return Collection
     */
    public function getTags(int $userId, ?string $search = null): Collection
    {
        $query = Tag::query();

        if ($search) {
            $query->where('name', 'like', '%' . trim($search) . '%');
        }

        $userTags = $query->orderBy('name', 'asc')->get();

        $defaultTags = $this->getDefaultTags($userId);
        if ($search) {
            $searchTerm = trim(strtolower($search));
            $defaultTags = $defaultTags->filter(function ($tag) use ($searchTerm) {
                return str_contains(strtolower($tag->name), $searchTerm);
            });
        }

        $mergedTags = $userTags->concat($defaultTags);

        // Sort alphabetically by name
        $sorted = $mergedTags->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return new Collection($sorted->all());
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
        if ($id < 0) {
            $defaultTag = $this->getDefaultTags($userId)->firstWhere('id', $id);
            if ($defaultTag) {
                return $defaultTag;
            }
        }
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
        if ($id < 0) {
            abort(422, 'System tags cannot be modified.');
        }
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
        if ($id < 0) {
            abort(422, 'System tags cannot be deleted.');
        }
        $tag = $this->getTagById($id, $userId);
        return $tag->delete();
    }

    /**
     * Get the validation rules for tag name.
     *
     * @param \App\Models\User|mixed $user
     * @param int|null $ignoreId
     * @return array
     */
    public function getNameRules($user, ?int $ignoreId = null): array
    {
        $uniqueRule = Rule::unique('tags', 'name')
            ->where(fn ($q) => $q->whereIn('user_id', function ($query) use ($user) {
                $query->select('id')
                    ->from('users')
                    ->where('unid', $user->unid);
            }));

        if ($ignoreId) {
            $uniqueRule->ignore($ignoreId);
        }

        $defaultNames = $this->getDefaultTags()->pluck('name')->all();

        return [
            'required',
            'string',
            'max:50',
            $uniqueRule,
            function ($attribute, $value, $fail) use ($defaultNames) {
                if (in_array(strtolower(trim($value)), array_map('strtolower', $defaultNames))) {
                    $fail('The ' . $attribute . ' cannot be a system/default tag name.');
                }
            }
        ];
    }

    /**
     * Get the validation rules for tag color.
     *
     * @return string
     */
    public function getColorRules(): string
    {
        return 'required|string|max:7';
    }

    /**
     * Resolve a tag (either default or custom) by ID or name for a given user.
     *
     * @param int $userId
     * @param int|null $tagId
     * @param string|null $tagName
     * @return Tag|null
     */
    public function resolveTag(int $userId, $tagId = null, $tagName = null): ?Tag
    {
        $tags = $this->getTags($userId);
        if ($tagId !== null) {
            return $tags->firstWhere('id', (int) $tagId);
        }
        if ($tagName !== null && trim($tagName) !== '') {
            $nameLower = strtolower(trim($tagName));
            return $tags->first(function ($t) use ($nameLower) {
                return strtolower(trim($t->name)) === $nameLower;
            });
        }
        return null;
    }
}
