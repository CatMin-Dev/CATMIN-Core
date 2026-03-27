<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailer\Controllers\Admin\MailerController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/mailer/manage', [MailerController::class, 'index'])
            ->middleware('catmin.permission:module.mailer.list')
            ->name('mailer.manage');

        Route::put('/mailer/config', [MailerController::class, 'updateConfig'])
            ->middleware('catmin.permission:module.mailer.config')
            ->name('mailer.config.update');

        Route::get('/mailer/templates/create', [MailerController::class, 'createTemplate'])
            ->middleware('catmin.permission:module.mailer.create')
            ->name('mailer.templates.create');

        Route::post('/mailer/templates', [MailerController::class, 'storeTemplate'])
            ->middleware('catmin.permission:module.mailer.create')
            ->name('mailer.templates.store');

        Route::get('/mailer/templates/{template}/edit', [MailerController::class, 'editTemplate'])
            ->middleware('catmin.permission:module.mailer.edit')
            ->name('mailer.templates.edit');

        Route::put('/mailer/templates/{template}', [MailerController::class, 'updateTemplate'])
            ->middleware('catmin.permission:module.mailer.edit')
            ->name('mailer.templates.update');
    });
