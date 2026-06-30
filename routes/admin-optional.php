<?php

use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\CaseStudyController;
use App\Http\Controllers\Admin\TeamController;
use Illuminate\Support\Facades\Route;

// Careers.
if (config('template.features.careers')) {
    Route::middleware('can:careers.view')->group(function () {
        Route::get('careers', [CareerController::class, 'index'])->name('careers.index');
    });
    Route::middleware('can:careers.create')->group(function () {
        Route::get('careers/create', [CareerController::class, 'create'])->name('careers.create');
        Route::post('careers', [CareerController::class, 'store'])->name('careers.store');
    });
    Route::middleware('can:careers.update')->group(function () {
        Route::get('careers/{career:id}/edit', [CareerController::class, 'edit'])->name('careers.edit');
        Route::put('careers/{career:id}', [CareerController::class, 'update'])->name('careers.update');
    });
    Route::middleware('can:careers.delete')->group(function () {
        Route::delete('careers/{career:id}', [CareerController::class, 'destroy'])->name('careers.destroy');
    });
}

// Case Studies.
if (config('template.features.case_studies')) {
    Route::middleware('can:case_studies.view')->group(function () {
        Route::get('case-studies', [CaseStudyController::class, 'index'])->name('case-studies.index');
    });
    Route::middleware('can:case_studies.create')->group(function () {
        Route::get('case-studies/create', [CaseStudyController::class, 'create'])->name('case-studies.create');
        Route::post('case-studies', [CaseStudyController::class, 'store'])->name('case-studies.store');
    });
    Route::middleware('can:case_studies.update')->group(function () {
        Route::get('case-studies/{case_study:id}/edit', [CaseStudyController::class, 'edit'])->name('case-studies.edit');
        Route::put('case-studies/{case_study:id}', [CaseStudyController::class, 'update'])->name('case-studies.update');
    });
    Route::middleware('can:case_studies.delete')->group(function () {
        Route::delete('case-studies/{case_study:id}', [CaseStudyController::class, 'destroy'])->name('case-studies.destroy');
    });
}

// Team.
if (config('template.features.teams')) {
    Route::middleware('can:teams.view')->group(function () {
        Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    });
    Route::middleware('can:teams.create')->group(function () {
        Route::get('teams/create', [TeamController::class, 'create'])->name('teams.create');
        Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
    });
    Route::middleware('can:teams.update')->group(function () {
        Route::get('teams/{team:id}/edit', [TeamController::class, 'edit'])->name('teams.edit');
        Route::put('teams/{team:id}', [TeamController::class, 'update'])->name('teams.update');
    });
    Route::middleware('can:teams.delete')->group(function () {
        Route::delete('teams/{team:id}', [TeamController::class, 'destroy'])->name('teams.destroy');
    });
}
