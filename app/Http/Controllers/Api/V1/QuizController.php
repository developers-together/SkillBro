<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LectureType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreQuizAttemptRequest;
use App\Http\Requests\Course\StoreQuizRequest;
use App\Http\Requests\Course\UpdateQuizRequest;
use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\QuizResource;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function store(StoreQuizRequest $request, Lecture $lecture): JsonResponse
    {
        abort_if($lecture->type !== LectureType::Quiz, 422, 'Quizzes can only be attached to quiz lectures.');

        $this->authorize('create', [Quiz::class, $lecture]);

        abort_if($lecture->quiz()->exists(), 422, 'Quiz already exists for this lecture.');

        $quiz = DB::transaction(function () use ($request, $lecture): Quiz {
            $quiz = Quiz::create([
                'lecture_id' => $lecture->id,
                'pass_percentage' => $request->integer('pass_percentage', 70),
            ]);

            $this->persistQuestions($quiz, collect($request->input('questions')));

            return $quiz;
        });

        return response()->json(
            new QuizResource($quiz->fresh()->load('questions.answers')),
            201,
        );
    }

    public function update(UpdateQuizRequest $request, Lecture $lecture): JsonResponse
    {
        $quiz = $lecture->quiz;
        abort_if($quiz === null, 404, 'Quiz not found for lecture.');

        $this->authorize('update', $quiz);

        $quiz = DB::transaction(function () use ($request, $quiz): Quiz {
            if ($request->has('pass_percentage')) {
                $quiz->update([
                    'pass_percentage' => $request->integer('pass_percentage'),
                ]);
            }

            if ($request->has('questions')) {
                $quiz->questions()->delete();
                $this->persistQuestions($quiz, collect($request->input('questions')));
            }

            return $quiz;
        });

        return response()->json(new QuizResource($quiz->fresh()->load('questions.answers')));
    }

    public function attempt(StoreQuizAttemptRequest $request, Lecture $lecture): JsonResponse
    {
        $quiz = $lecture->quiz;
        abort_if($quiz === null, 404, 'Quiz not found for lecture.');

        $enrollment = Enrollment::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('course.sections', function ($query) use ($lecture): void {
                $query->where('id', $lecture->section_id);
            })
            ->first();

        abort_if($enrollment === null, 403, 'You are not enrolled in this course.');

        $this->authorize('attempt', [$quiz, $enrollment]);

        $quiz->load('questions.answers');

        $answersPayload = collect($request->input('answers'));
        $scoreResult = $this->calculateScore($quiz, $answersPayload);

        $attempt = QuizAttempt::create([
            'enrollment_id' => $enrollment->id,
            'quiz_id' => $quiz->id,
            'score' => $scoreResult['score'],
            'passed' => $scoreResult['passed'],
            'answers' => $answersPayload->values()->all(),
            'created_at' => now(),
        ]);

        return response()->json(new QuizAttemptResource($attempt), 201);
    }

    public function attempts(Lecture $lecture, Request $request): AnonymousResourceCollection
    {
        $quiz = $lecture->quiz;
        abort_if($quiz === null, 404, 'Quiz not found for lecture.');

        $enrollment = Enrollment::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('course.sections', function ($query) use ($lecture): void {
                $query->where('id', $lecture->section_id);
            })
            ->first();

        abort_if($enrollment === null, 403, 'You are not enrolled in this course.');

        $this->authorize('viewAttempts', [$quiz, $enrollment]);

        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('enrollment_id', $enrollment->id)
            ->latest('id')
            ->paginate(20);

        return QuizAttemptResource::collection($attempts);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $questions
     */
    private function persistQuestions(Quiz $quiz, Collection $questions): void
    {
        $questions->each(function (array $question, int $index) use ($quiz): void {
            $answers = collect($question['answers'] ?? []);

            if (! $answers->contains(fn (array $answer): bool => (bool) ($answer['is_correct'] ?? false))) {
                throw ValidationException::withMessages([
                    'questions' => ['Each question must have at least one correct answer.'],
                ]);
            }

            $quizQuestion = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => (string) $question['question'],
                'position' => (int) ($question['position'] ?? ($index + 1)),
            ]);

            $answers->each(function (array $answer) use ($quizQuestion): void {
                QuizAnswer::create([
                    'question_id' => $quizQuestion->id,
                    'answer' => (string) $answer['answer'],
                    'is_correct' => (bool) $answer['is_correct'],
                ]);
            });
        });
    }

    /**
     * @param  Collection<int, array{question_id:int,answer_id:int}>  $payload
     * @return array{score:int,passed:bool}
     */
    private function calculateScore(Quiz $quiz, Collection $payload): array
    {
        $questionIds = $quiz->questions->pluck('id');
        $totalQuestions = $questionIds->count();

        if ($totalQuestions === 0) {
            return ['score' => 0, 'passed' => false];
        }

        $correctCount = 0;

        $submittedAnswers = $payload
            ->keyBy(fn (array $item): int => (int) $item['question_id']);

        foreach ($submittedAnswers as $item) {
            $questionId = (int) $item['question_id'];
            $answerId = (int) $item['answer_id'];

            if (! $questionIds->contains($questionId)) {
                continue;
            }

            $isCorrect = QuizAnswer::query()
                ->where('id', $answerId)
                ->where('question_id', $questionId)
                ->where('is_correct', true)
                ->exists();

            if ($isCorrect) {
                $correctCount++;
            }
        }

        $score = (int) floor(($correctCount / $totalQuestions) * 100);

        return [
            'score' => $score,
            'passed' => $score >= $quiz->pass_percentage,
        ];
    }
}
