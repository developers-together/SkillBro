<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\InstructorController;
use App\Http\Controllers\Api\V1\LectureController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QuizController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SectionController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Auth (guest) ─────────────────────────────────────────────────────────
    Route::prefix('auth')->middleware('throttle:6,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    // ── Public ───────────────────────────────────────────────────────────────
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('tags', [TagController::class, 'index']);
    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{course}', [CourseController::class, 'show']);
    Route::get('courses/{course}/sections', [SectionController::class, 'index']);
    Route::get('courses/{course}/reviews', [ReviewController::class, 'index']);
    Route::get('instructors/{user}', [InstructorController::class, 'show']);

    // ── Authenticated ────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Profile
        Route::get('user', [ProfileController::class, 'show']);
        Route::put('user', [ProfileController::class, 'update']);
        Route::post('user/avatar', [ProfileController::class, 'uploadAvatar']);

        // Categories (admin-only writes)
        Route::middleware('role:admin')->group(function () {
            Route::post('categories', [CategoryController::class, 'store']);
            Route::put('categories/{category}', [CategoryController::class, 'update']);
            Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
            Route::post('tags', [TagController::class, 'store']);
            Route::delete('tags/{tag}', [TagController::class, 'destroy']);
        });

        // Courses
        Route::post('courses', [CourseController::class, 'store']);
        Route::put('courses/{course}', [CourseController::class, 'update']);
        Route::delete('courses/{course}', [CourseController::class, 'destroy']);
        Route::post('courses/{course}/thumbnail', [CourseController::class, 'uploadThumbnail']);
        Route::post('courses/{course}/submit', [CourseController::class, 'submit']);
        Route::post('courses/{course}/archive', [CourseController::class, 'archive']);
        Route::post('courses/{course}/publish', [CourseController::class, 'publish'])
            ->middleware('role:admin');

        // Sections
        Route::post('courses/{course}/sections', [SectionController::class, 'store']);
        Route::put('courses/{course}/sections/{section}', [SectionController::class, 'update']);
        Route::delete('courses/{course}/sections/{section}', [SectionController::class, 'destroy']);
        Route::post('courses/{course}/sections/reorder', [SectionController::class, 'reorder']);

        // Lectures
        Route::post('sections/{section}/lectures', [LectureController::class, 'store']);
        Route::put('sections/{section}/lectures/{lecture}', [LectureController::class, 'update']);
        Route::delete('sections/{section}/lectures/{lecture}', [LectureController::class, 'destroy']);
        Route::post('sections/{section}/lectures/reorder', [LectureController::class, 'reorder']);

        // Quizzes
        Route::post('lectures/{lecture}/quiz', [QuizController::class, 'store']);
        Route::put('lectures/{lecture}/quiz', [QuizController::class, 'update']);
        Route::post('lectures/{lecture}/quiz/attempt', [QuizController::class, 'attempt']);
        Route::get('lectures/{lecture}/quiz/attempts', [QuizController::class, 'attempts']);

        // Enrollments
        Route::get('enrollments', [EnrollmentController::class, 'index']);
        Route::post('enrollments', [EnrollmentController::class, 'store']);
        Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
        Route::post(
            'enrollments/{enrollment}/lectures/{lecture}/complete',
            [EnrollmentController::class, 'completeLecture']
        );

        // Reviews
        Route::post('courses/{course}/reviews', [ReviewController::class, 'store']);
        Route::put('courses/{course}/reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('courses/{course}/reviews/{review}', [ReviewController::class, 'destroy']);
        Route::post('courses/{course}/reviews/{review}/reply', [ReviewController::class, 'reply']);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::put('notifications/{notificationId}', [NotificationController::class, 'markRead']);

        // Admin
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('users', [Admin\UserController::class, 'index']);
            Route::put('users/{user}/ban', [Admin\UserController::class, 'ban']);
            Route::put('users/{user}/role', [Admin\UserController::class, 'changeRole']);
            Route::get('courses', [Admin\CourseController::class, 'index']);
            Route::get('stats', Admin\StatsController::class);
        });
    });
});
