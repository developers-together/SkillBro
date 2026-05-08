<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with('children.children')
            ->whereNull('parent_id')
            ->get();

        return response()->json(CategoryResource::collection($categories));
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'slug' => Str::slug($request->input('name')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug' => ['required', 'string', 'unique:categories,slug'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $category = Category::create($data);

        return response()->json(new CategoryResource($category), 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        if ($request->has('name')) {
            $request->merge([
                'slug' => Str::slug($request->input('name')),
            ]);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:categories,name,'.$category->id],
            'slug' => ['sometimes', 'string', 'unique:categories,slug,'.$category->id],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:categories,id',
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    if ($value === null) {
                        return;
                    }

                    $ancestorId = (int) $value;
                    $visited = [];

                    while ($ancestorId !== 0 && ! in_array($ancestorId, $visited, true)) {
                        if ($ancestorId === $category->id) {
                            $fail('The selected parent category is invalid.');

                            return;
                        }

                        $visited[] = $ancestorId;
                        $ancestorId = (int) (Category::query()
                            ->whereKey($ancestorId)
                            ->value('parent_id') ?? 0);
                    }
                },
            ],
        ]);

        $category->update($data);

        return response()->json(new CategoryResource($category->fresh()));
    }

    public function destroy(Category $category): JsonResponse
    {
        abort_if(
            $category->courses()->exists(),
            422,
            'Cannot delete category with associated courses.'
        );

        $category->delete();

        return response()->json(null, 204);
    }
}
