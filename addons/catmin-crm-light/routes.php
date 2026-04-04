<?php

use Addons\CatminCrmLight\Controllers\Admin\CrmCompanyController;
use Addons\CatminCrmLight\Controllers\Admin\CrmContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.crm.')
    ->group(function (): void {
        Route::get('/crm/contacts', [CrmContactController::class, 'index'])
            ->middleware('catmin.permission:module.crm.list')
            ->name('contacts.index');

        Route::post('/crm/contacts', [CrmContactController::class, 'store'])
            ->middleware('catmin.permission:module.crm.create')
            ->name('contacts.store');

        Route::get('/crm/contacts/{crmContact}', [CrmContactController::class, 'show'])
            ->middleware('catmin.permission:module.crm.list')
            ->name('contacts.show');

        Route::put('/crm/contacts/{crmContact}', [CrmContactController::class, 'update'])
            ->middleware('catmin.permission:module.crm.edit')
            ->name('contacts.update');

        Route::delete('/crm/contacts/{crmContact}', [CrmContactController::class, 'destroy'])
            ->middleware('catmin.permission:module.crm.delete')
            ->name('contacts.destroy');

        Route::post('/crm/contacts/{crmContact}/notes', [CrmContactController::class, 'addNote'])
            ->middleware('catmin.permission:module.crm.timeline')
            ->name('contacts.notes.store');

        Route::post('/crm/contacts/{crmContact}/interactions', [CrmContactController::class, 'addInteraction'])
            ->middleware('catmin.permission:module.crm.timeline')
            ->name('contacts.interactions.store');

        Route::post('/crm/contacts/{crmContact}/tasks', [CrmContactController::class, 'addTask'])
            ->middleware('catmin.permission:module.crm.tasks')
            ->name('contacts.tasks.store');

        Route::patch('/crm/tasks/{crmTask}/complete', [CrmContactController::class, 'completeTask'])
            ->middleware('catmin.permission:module.crm.tasks')
            ->name('tasks.complete');

        Route::patch('/crm/contacts/{crmContact}/pipeline', [CrmContactController::class, 'movePipeline'])
            ->middleware('catmin.permission:module.crm.pipeline')
            ->name('contacts.pipeline.move');

        Route::get('/crm/pipeline', [CrmContactController::class, 'pipeline'])
            ->middleware('catmin.permission:module.crm.pipeline')
            ->name('pipeline.index');

        Route::post('/crm/contacts/{crmContact}/mail', [CrmContactController::class, 'sendMail'])
            ->middleware('catmin.permission:module.crm.edit')
            ->name('contacts.mail.send');

        Route::get('/crm/companies', [CrmCompanyController::class, 'index'])
            ->middleware('catmin.permission:module.crm.list')
            ->name('companies.index');

        Route::post('/crm/companies', [CrmCompanyController::class, 'store'])
            ->middleware('catmin.permission:module.crm.create')
            ->name('companies.store');

        Route::put('/crm/companies/{crmCompany}', [CrmCompanyController::class, 'update'])
            ->middleware('catmin.permission:module.crm.edit')
            ->name('companies.update');

        Route::delete('/crm/companies/{crmCompany}', [CrmCompanyController::class, 'destroy'])
            ->middleware('catmin.permission:module.crm.delete')
            ->name('companies.destroy');
    });