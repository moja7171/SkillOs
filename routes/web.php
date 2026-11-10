<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FriendsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SubtitleGlossaryController;
use App\Http\Controllers\VideoViewController;
use App\Http\Controllers\WeakSpotsController;
use App\Models\Lesson;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');
    Route::delete('courses/{course}/enroll', [EnrollmentController::class, 'destroy'])->name('courses.unenroll');

    Route::get('courses/{course}/learn', [LessonController::class, 'continue'])->name('courses.learn');
    Route::get('courses/{course}/lessons/{lesson:slug}', [LessonController::class, 'show'])->scopeBindings()->name('lessons.show');
    Route::post('lessons/{lesson}/mark-done', [LessonController::class, 'markDone'])->name('lessons.mark-done');
    Route::post('lesson-videos/{lessonVideo}/watched', [VideoViewController::class, 'store'])->name('lesson-videos.watched');
    Route::get('lesson-videos/{lessonVideo}/glossary', [SubtitleGlossaryController::class, 'show'])->name('lesson-videos.glossary');
    Route::post('lesson-videos/{lessonVideo}/lookup', [SubtitleGlossaryController::class, 'lookup'])->middleware('throttle:40,1')->name('lesson-videos.lookup');
    // Old bookmark form; lesson pages now live under their course.
    Route::get('lessons/{lesson}', fn (Lesson $lesson) => redirect()->route('lessons.show', [$lesson->course, $lesson], 301));

    Route::post('activities/{activity}/start', [SessionController::class, 'start'])->name('session.start');
    Route::get('activities/{activity}', [SessionController::class, 'open'])->name('session.open');
    Route::get('activities/{activity}/start', [SessionController::class, 'resumeOrHome']);
    Route::post('reviews/start', [ReviewController::class, 'start'])->name('reviews.start');
    Route::get('search', SearchController::class)->name('search');
    Route::get('friends', FriendsController::class)->name('friends');
    Route::get('weak-spots', WeakSpotsController::class)->name('weak-spots');
    Route::get('session/{attempt}', [SessionController::class, 'show'])->name('session.show');
    Route::post('session/{attempt}/submit', [SessionController::class, 'submit'])->name('session.submit');
    Route::post('session/{attempt}/report', [SessionController::class, 'report'])->name('session.report');
    Route::post('session/{attempt}/give-up', [SessionController::class, 'giveUp'])->name('session.give-up');
    Route::delete('session/{attempt}', [SessionController::class, 'cancel'])->name('session.cancel');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
