<?php

use Addons\CatminBooking\Controllers\Admin\BookingCalendarController;
use Addons\CatminBooking\Controllers\Admin\BookingController;
use Addons\CatminBooking\Controllers\Admin\BookingServiceController;
use Addons\CatminBooking\Controllers\Admin\BookingSlotController;
use Addons\CatminBooking\Controllers\Api\BookingApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.booking.')
    ->group(function (): void {
        Route::get('/booking/services', [BookingServiceController::class, 'index'])
            ->middleware('catmin.permission:module.booking.list')
            ->name('services.index');

        Route::post('/booking/services', [BookingServiceController::class, 'store'])
            ->middleware('catmin.permission:module.booking.create')
            ->name('services.store');

        Route::get('/booking/services/{bookingService}/edit', [BookingServiceController::class, 'edit'])
            ->middleware('catmin.permission:module.booking.edit')
            ->name('services.edit');

        Route::put('/booking/services/{bookingService}', [BookingServiceController::class, 'update'])
            ->middleware('catmin.permission:module.booking.edit')
            ->name('services.update');

        Route::delete('/booking/services/{bookingService}', [BookingServiceController::class, 'destroy'])
            ->middleware('catmin.permission:module.booking.delete')
            ->name('services.destroy');

        Route::get('/booking/slots', [BookingSlotController::class, 'index'])
            ->middleware('catmin.permission:module.booking.manage_slots')
            ->name('slots.index');

        Route::post('/booking/slots', [BookingSlotController::class, 'store'])
            ->middleware('catmin.permission:module.booking.manage_slots')
            ->name('slots.store');

        Route::put('/booking/slots/{bookingSlot}', [BookingSlotController::class, 'update'])
            ->middleware('catmin.permission:module.booking.manage_slots')
            ->name('slots.update');

        Route::delete('/booking/slots/{bookingSlot}', [BookingSlotController::class, 'destroy'])
            ->middleware('catmin.permission:module.booking.manage_slots')
            ->name('slots.destroy');

        Route::get('/booking/bookings', [BookingController::class, 'index'])
            ->middleware('catmin.permission:module.booking.list')
            ->name('bookings.index');

        Route::post('/booking/bookings', [BookingController::class, 'store'])
            ->middleware('catmin.permission:module.booking.create')
            ->name('bookings.store');

        Route::patch('/booking/bookings/{booking}/status', [BookingController::class, 'updateStatus'])
            ->middleware('catmin.permission:module.booking.edit')
            ->name('bookings.status');

        Route::get('/booking/calendar', [BookingCalendarController::class, 'index'])
            ->middleware('catmin.permission:module.booking.list')
            ->name('calendar.index');

        Route::get('/booking/api/services', [BookingApiController::class, 'services'])
            ->middleware('catmin.permission:module.booking.api')
            ->name('api.services');

        Route::get('/booking/api/slots', [BookingApiController::class, 'slots'])
            ->middleware('catmin.permission:module.booking.api')
            ->name('api.slots');

        Route::get('/booking/api/calendar', [BookingApiController::class, 'calendar'])
            ->middleware('catmin.permission:module.booking.api')
            ->name('api.calendar');
    });