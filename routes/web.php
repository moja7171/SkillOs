<?php

use App\Http\Controllers\LearningItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SkillController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'learning-items.index' : 'login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('learning-items.index'))->name('dashboard');

    Route::resource('learning-items', LearningItemController::class)
        ->except(['destroy']);

    Route::post('learning-items/{learning_item}/generate-design', [LearningItemController::class, 'generateDesign'])
        ->name('learning-items.generate-design');

    Route::post('learning-items/{learning_item}/approve-design', [LearningItemController::class, 'approveDesign'])
        ->name('learning-items.approve-design');

    Route::get('skills/{skill}', [SkillController::class, 'show'])->name('skills.show');
    Route::post('skills/{skill}/generate-content', [SkillController::class, 'generateContent'])->name('skills.generate-content');
    Route::post('skills/{skill}/video', [SkillController::class, 'storeVideo'])->name('skills.video.store');
    Route::delete('skills/{skill}/video', [SkillController::class, 'destroyVideo'])->name('skills.video.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
