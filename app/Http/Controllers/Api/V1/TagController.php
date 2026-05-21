<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    protected $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $tags = $this->tagService->getTags($request->user()->id, $request->search);
        return response()->json($tags);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => $this->tagService->getNameRules($user),
            'color' => $this->tagService->getColorRules(),
        ]);

        $tag = $this->tagService->createTag(array_merge($validated, ['user_id' => $user->id]));

        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tag = $this->tagService->getTagById($id, $request->user()->id);
        return response()->json($tag);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => $this->tagService->getNameRules($user, $id),
            'color' => $this->tagService->getColorRules(),
        ]);

        $tag = $this->tagService->updateTag($id, $user->id, $validated);

        return response()->json($tag);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->tagService->deleteTag($id, $request->user()->id);
        return response()->json(null, 204);
    }
}
