<?php

use App\Enums\LectureType;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('quiz api', function () {
    it('allows instructor to create quiz for own quiz lecture', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lecture = Lecture::factory()->create([
            'section_id' => $section->id,
            'type' => LectureType::Quiz,
        ]);

        $payload = [
            'pass_percentage' => 80,
            'questions' => [
                [
                    'question' => 'What is 2 + 2?',
                    'answers' => [
                        ['answer' => '3', 'is_correct' => false],
                        ['answer' => '4', 'is_correct' => true],
                    ],
                ],
            ],
        ];

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/lectures/{$lecture->id}/quiz", $payload)
            ->assertCreated()
            ->assertJsonPath('pass_percentage', 80)
            ->assertJsonCount(1, 'questions');

        $this->assertDatabaseHas('quizzes', ['lecture_id' => $lecture->id, 'pass_percentage' => 80]);
    });

    it('rejects creating quiz for non-quiz lecture type', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lecture = Lecture::factory()->create([
            'section_id' => $section->id,
            'type' => LectureType::Video,
        ]);

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/lectures/{$lecture->id}/quiz", [
                'questions' => [
                    [
                        'question' => 'Sample',
                        'answers' => [
                            ['answer' => 'A', 'is_correct' => true],
                            ['answer' => 'B', 'is_correct' => false],
                        ],
                    ],
                ],
            ])
            ->assertStatus(422);
    });

    it('allows enrolled student to submit attempt and get score', function () {
        $instructor = User::factory()->instructor()->create();
        $student = User::factory()->create();

        $course = Course::factory()->published()->create(['user_id' => $instructor->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lecture = Lecture::factory()->create([
            'section_id' => $section->id,
            'type' => LectureType::Quiz,
        ]);

        $quiz = Quiz::factory()->create([
            'lecture_id' => $lecture->id,
            'pass_percentage' => 70,
        ]);

        $question = QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'position' => 1,
        ]);

        $wrong = QuizAnswer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $correct = QuizAnswer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        unset($wrong, $enrollment);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/lectures/{$lecture->id}/quiz/attempt", [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'answer_id' => $correct->id,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('score', 100)
            ->assertJsonPath('passed', true);
    });

    it('forbids non-enrolled student from attempting quiz', function () {
        $instructor = User::factory()->instructor()->create();
        $student = User::factory()->create();

        $course = Course::factory()->published()->create(['user_id' => $instructor->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lecture = Lecture::factory()->create([
            'section_id' => $section->id,
            'type' => LectureType::Quiz,
        ]);

        $quiz = Quiz::factory()->create(['lecture_id' => $lecture->id]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $answer = QuizAnswer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/lectures/{$lecture->id}/quiz/attempt", [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'answer_id' => $answer->id,
                    ],
                ],
            ])
            ->assertForbidden();
    });

    it('returns only current student attempts for quiz', function () {
        $instructor = User::factory()->instructor()->create();
        $student = User::factory()->create();

        $course = Course::factory()->published()->create(['user_id' => $instructor->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lecture = Lecture::factory()->create([
            'section_id' => $section->id,
            'type' => LectureType::Quiz,
        ]);

        $quiz = Quiz::factory()->create(['lecture_id' => $lecture->id]);

        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        QuizAttempt::create([
            'enrollment_id' => $enrollment->id,
            'quiz_id' => $quiz->id,
            'score' => 80,
            'passed' => true,
            'answers' => [],
            'created_at' => now(),
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/lectures/{$lecture->id}/quiz/attempts")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        unset($enrollment);
    });
});
