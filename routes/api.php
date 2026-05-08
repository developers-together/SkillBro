<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\InstructorController;
use App\Http\Controllers\Api\V1\LectureController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SectionController;
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
    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{course}', [CourseController::class, 'show']);
    Route::get('courses/{course}/sections', [SectionController::class, 'index']);
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

        // Enrollments
        Route::get('enrollments', [EnrollmentController::class, 'index']);
        Route::post('enrollments', [EnrollmentController::class, 'store']);
        Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
        Route::post(
            'enrollments/{enrollment}/lectures/{lecture}/complete',
            [EnrollmentController::class, 'completeLecture']
        );

        // Admin
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('users', [Admin\UserController::class, 'index']);
            Route::put('users/{user}/ban', [Admin\UserController::class, 'ban']);
            Route::put('users/{user}/role', [Admin\UserController::class, 'changeRole']);
            Route::get('courses', [Admin\CourseController::class, 'index']);
        });
    });
});
