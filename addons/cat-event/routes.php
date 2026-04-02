<?php

use Illuminate\Support\Facades\Route;
use Addons\CatEvent\Controllers\Admin\EventController;
use Addons\CatEvent\Controllers\Admin\ParticipantController;
use Addons\CatEvent\Controllers\Admin\TicketController;
use Addons\CatEvent\Controllers\Admin\CheckinController;
use Addons\CatEvent\Controllers\Admin\SessionController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {

        // ── Événements CRUD ────────────────────────────────────────────────────

        Route::get('/events', [EventController::class, 'index'])
            ->middleware('catmin.permission:module.events.list')
            ->name('events.index');

        Route::get('/events/create', [EventController::class, 'create'])
            ->middleware('catmin.permission:module.events.create')
            ->name('events.create');

        Route::post('/events', [EventController::class, 'store'])
            ->middleware('catmin.permission:module.events.create')
            ->name('events.store');

        Route::get('/events/{event}/edit', [EventController::class, 'edit'])
            ->middleware('catmin.permission:module.events.edit')
            ->name('events.edit');

        Route::put('/events/{event}', [EventController::class, 'update'])
            ->middleware('catmin.permission:module.events.edit')
            ->name('events.update');

        Route::delete('/events/{event}', [EventController::class, 'destroy'])
            ->middleware('catmin.permission:module.events.delete')
            ->name('events.destroy');

        Route::patch('/events/{event}/toggle-status', [EventController::class, 'toggleStatus'])
            ->middleware('catmin.permission:module.events.edit')
            ->name('events.toggle_status');

        // ── Sessions ───────────────────────────────────────────────────────────

        Route::post('/events/{event}/sessions', [SessionController::class, 'store'])
            ->middleware('catmin.permission:module.events.edit')
            ->name('events.sessions.store');

        Route::delete('/events/{event}/sessions/{session}', [SessionController::class, 'destroy'])
            ->middleware('catmin.permission:module.events.edit')
            ->name('events.sessions.destroy');

        // ── Participants ───────────────────────────────────────────────────────

        Route::get('/events/{event}/participants', [ParticipantController::class, 'index'])
            ->middleware('catmin.permission:module.events.list')
            ->name('events.participants');

        Route::post('/events/{event}/participants', [ParticipantController::class, 'store'])
            ->middleware('catmin.permission:module.events.create')
            ->name('events.participants.store');

        Route::patch('/events/{event}/participants/{participant}/status', [ParticipantController::class, 'updateStatus'])
            ->middleware('catmin.permission:module.events.edit')
            ->name('events.participants.status');

        Route::delete('/events/{event}/participants/{participant}', [ParticipantController::class, 'destroy'])
            ->middleware('catmin.permission:module.events.delete')
            ->name('events.participants.destroy');

        // ── Billets ────────────────────────────────────────────────────────────

        Route::get('/events/{event}/tickets', [TicketController::class, 'index'])
            ->middleware('catmin.permission:module.events.list')
            ->name('events.tickets');

        Route::patch('/events/{event}/tickets/{ticket}/cancel', [TicketController::class, 'cancel'])
            ->middleware('catmin.permission:module.events.edit')
            ->name('events.tickets.cancel');

        // ── Check-in ───────────────────────────────────────────────────────────

        Route::get('/events/{event}/checkin', [CheckinController::class, 'index'])
            ->middleware('catmin.permission:module.events.checkin')
            ->name('events.checkin');

        Route::post('/events/{event}/checkin', [CheckinController::class, 'store'])
            ->middleware('catmin.permission:module.events.checkin')
            ->name('events.checkin.store');
    });
