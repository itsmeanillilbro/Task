<?php

use App\Http\Controllers\Admin\ResumeController;
use App\Http\Controllers\Admin\ScoringSystemController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth','admin'])->prefix('admin')->group(function () {
 Route::resource('scoring-system',ScoringSystemController::class)->except(['show','edit']);
 Route::resource('resume',ResumeController::class);
 Route::get('/resumes/batch/{batchId}', [ResumeController::class, 'batch'])->name('resume.batch');
 Route::get('/resumes/export', [ResumeController::class, 'export'])->name('resumes.export');
});

require __DIR__.'/auth.php';
