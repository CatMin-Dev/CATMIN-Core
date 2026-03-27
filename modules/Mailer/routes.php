<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailer\Controllers\Admin\MailerController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/mailer/manage', [MailerController::class, 'index'])
            ->name('mailer.manage');

        Route::put('/mailer/config', [MailerController::class, 'updateConfig'])
            ->name('mailer.config.update');

        Route::get('/mailer/templates/create', [MailerController::class, 'createTemplate'])
            ->name('mailer.templates.create');

        Route::post('/mailer/templates', [MailerController::class, 'storeTemplate'])
            ->name('mailer.templates.store');

        Route::get('/mailer/templates/{template}/edit', [MailerController::class, 'editTemplate'])
            ->name('mailer.templates.edit');

        Route::put('/mailer/templates/{template}', [MailerController::class, 'updateTemplate'])
            ->name('mailer.templates.update');
    });
