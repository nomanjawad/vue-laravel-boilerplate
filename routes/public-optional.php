<?php

use App\Http\Controllers\Public\CareerController;
use App\Http\Controllers\Public\CaseStudyController;
use Illuminate\Support\Facades\Route;

if (config('template.features.careers')) {
    Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
    Route::get('/careers/{career:slug}', [CareerController::class, 'show'])->name('careers.show');
}

if (config('template.features.case_studies')) {
    Route::get('/case-studies', [CaseStudyController::class, 'index'])->name('case-studies.index');
    Route::get('/case-studies/{caseStudy:slug}', [CaseStudyController::class, 'show'])->name('case-studies.show');
}
