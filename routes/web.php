<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');

    Route::get('enrollments/{enrollment}/edit', [EnrollmentController::class, 'edit'])->name('enrollments.edit');
    Route::put('enrollments/{enrollment}', [EnrollmentController::class, 'update'])->name('enrollments.update');

    Route::get('lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');

    Route::post('activities/{activity}/start', [SessionController::class, 'start'])->name('session.start');
    Route::post('plan-items/{planItem}/start', [SessionController::class, 'startPlanned'])->name('session.start-planned');
    Route::post('plan-items/{planItem}/skip', [PlanController::class, 'skip'])->name('plan-items.skip');
    Route::get('week', [PlanController::class, 'week'])->name('week');
    Route::get('session/{attempt}', [SessionController::class, 'show'])->name('session.show');
    Route::post('session/{attempt}/complete', [SessionController::class, 'complete'])->name('session.complete');
    Route::post('session/{attempt}/submit', [SessionController::class, 'submit'])->name('session.submit');
    Route::post('session/{attempt}/give-up', [SessionController::class, 'giveUp'])->name('session.give-up');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
