<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreLectureRequest;
use App\Http\Requests\Course\UpdateLectureRequest;
use App\Http\Resources\LectureResource;
use App\Models\Lecture;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LectureController extends Controller
{
    public function store(StoreLectureRequest $request, Section $section): JsonResponse
    {
        // Authorize against a lecture bound to the section so the policy can traverse to course
        $lecture = new Lecture($request->validated());
        $lecture->section_id = $section->id;
        $lecture->setRelation('section', $section);
        $this->authorize('create', $lecture);

        $position = $request->integer('position', $section->lectures()->max('position') + 1);

        $lecture = $section->lectures()->create($request->validated() + ['position' => $position]);

        return response()->json(new LectureResource($lecture), 201);
    }

    public function update(UpdateLectureRequest $request, Section $section, Lecture $lecture): JsonResponse
    {
        abort_unless($lecture->section_id === $section->id, 404);

        $this->authorize('update', $lecture);

        $lecture->update($request->validated());

        return response()->json(new LectureResource($lecture->fresh()));
    }

    public function destroy(Section $section, Lecture $lecture): JsonResponse
    {
        abort_unless($lecture->section_id === $section->id, 404);

        $this->authorize('delete', $lecture);

        $lecture->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Section $section): JsonResponse
    {
        $request->validate([
            'lectures' => ['required', 'array'],
            'lectures.*.id' => [
                'required',
                'integer',
                Rule::exists('lectures', 'id')->where('section_id', $section->id),
            ],
            'lectures.*.position' => ['required', 'integer', 'min:0'],
        ]);

        // Authorize via a lecture proxy so policy can reach the course owner
        $proxy = new Lecture(['section_id' => $section->id]);
        $proxy->setRelation('section', $section);
        $this->authorize('update', $proxy);

        foreach ($request->input('lectures') as $item) {
            Lecture::where('id', $item['id'])
                ->where('section_id', $section->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Lectures reordered.']);
    }
}
