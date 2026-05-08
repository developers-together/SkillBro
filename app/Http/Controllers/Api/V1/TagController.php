<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tags = Tag::query()->orderBy('name')->get();

        return TagResource::collection($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'slug' => Str::slug($request->string('name')->toString()),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tags,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:tags,slug'],
        ]);

        $tag = Tag::create($data);

        return response()->json(new TagResource($tag), 201);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(null, 204);
    }
}
