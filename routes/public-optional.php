<?php

use App\Http\Controllers\Public\CareerController;
use App\Http\Controllers\Public\CaseStudyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['module:careers', 'responsecache'])->group(function () {
    Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
    Route::get('/careers/{career:slug}', [CareerController::class, 'show'])->name('careers.show');
});

Route::middleware(['module:case_studies', 'responsecache'])->group(function () {
    Route::get('/case-studies', [CaseStudyController::class, 'index'])->name('case-studies.index');
    Route::get('/case-studies/{caseStudy:slug}', [CaseStudyController::class, 'show'])->name('case-studies.show');
});
