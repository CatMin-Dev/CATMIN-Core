<?php

use Addons\CatminForms\Controllers\Admin\FormController;
use Addons\CatminForms\Controllers\Admin\SubmissionController;
use Addons\CatminForms\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.frontend.available'])->group(function (): void {
    Route::get('/forms/{slug}', [PublicFormController::class, 'show'])
        ->where('slug', '[A-Za-z0-9\-]+')
        ->name('frontend.forms.show');

    Route::post('/forms/{slug}', [PublicFormController::class, 'submit'])
        ->where('slug', '[A-Za-z0-9\-]+')
        ->middleware('throttle:catmin-contact')
        ->name('frontend.forms.submit');
});

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/forms', [FormController::class, 'index'])
            ->middleware('catmin.permission:module.forms.list')
            ->name('forms.index');

        Route::post('/forms', [FormController::class, 'store'])
            ->middleware('catmin.permission:module.forms.create')
            ->name('forms.store');

        Route::get('/forms/{formDefinition}/edit', [FormController::class, 'edit'])
            ->middleware('catmin.permission:module.forms.edit')
            ->name('forms.edit');

        Route::put('/forms/{formDefinition}', [FormController::class, 'update'])
            ->middleware('catmin.permission:module.forms.edit')
            ->name('forms.update');

        Route::delete('/forms/{formDefinition}', [FormController::class, 'destroy'])
            ->middleware('catmin.permission:module.forms.delete')
            ->name('forms.destroy');

        Route::post('/forms/{formDefinition}/fields', [FormController::class, 'storeField'])
            ->middleware('catmin.permission:module.forms.edit')
            ->name('forms.fields.store');

        Route::delete('/forms/{formDefinition}/fields/{formField}', [FormController::class, 'destroyField'])
            ->middleware('catmin.permission:module.forms.edit')
            ->name('forms.fields.destroy');

        Route::get('/forms/submissions', [SubmissionController::class, 'index'])
            ->middleware('catmin.permission:module.forms.submissions')
            ->name('forms.submissions.index');

        Route::get('/forms/submissions/{formSubmission}', [SubmissionController::class, 'show'])
            ->middleware('catmin.permission:module.forms.submissions')
            ->name('forms.submissions.show');

        Route::patch('/forms/submissions/{formSubmission}/process', [SubmissionController::class, 'markProcessed'])
            ->middleware('catmin.permission:module.forms.submissions')
            ->name('forms.submissions.process');
    });
