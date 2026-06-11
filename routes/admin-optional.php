<?php

use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\CaseStudyController;
use App\Http\Controllers\Admin\TeamController;
use Illuminate\Support\Facades\Route;

if (config('template.features.careers')) {
    Route::resource('careers', CareerController::class)->except('show')->scoped(['career' => 'id']);
}

if (config('template.features.case_studies')) {
    Route::resource('case-studies', CaseStudyController::class)->except('show')->scoped(['case_study' => 'id']);
}

if (config('template.features.teams')) {
    Route::resource('teams', TeamController::class)->except('show')->scoped(['team' => 'id']);
}
