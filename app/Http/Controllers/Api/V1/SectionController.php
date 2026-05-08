<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreSectionRequest;
use App\Http\Requests\Course\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $sections = $course->sections()->with('lectures')->get();

        return response()->json(SectionResource::collection($sections));
    }

    public function store(StoreSectionRequest $request, Course $course): JsonResponse
    {
        // Build section with course relation pre-loaded so policy can traverse to it
        $section = new Section($request->validated());
        $section->course_id = $course->id;
        $section->setRelation('course', $course);
        $this->authorize('create', $section);

        $position = $request->integer('position', $course->sections()->max('position') + 1);

        $section = $course->sections()->create($request->validated() + ['position' => $position]);

        return response()->json(new SectionResource($section), 201);
    }

    public function update(UpdateSectionRequest $request, Course $course, Section $section): JsonResponse
    {
        $this->authorize('update', $section);

        $section->update($request->validated());

        return response()->json(new SectionResource($section->fresh()));
    }

    public function destroy(Course $course, Section $section): JsonResponse
    {
        $this->authorize('delete', $section);

        $section->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.id' => ['required', 'integer', 'exists:sections,id'],
            'sections.*.position' => ['required', 'integer', 'min:0'],
        ]);

        $this->authorize('update', $course);

        foreach ($request->input('sections') as $item) {
            Section::where('id', $item['id'])
                ->where('course_id', $course->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Sections reordered.']);
    }
}
