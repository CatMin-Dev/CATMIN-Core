<?php

namespace Addons\CatminBooking\Controllers\Admin;

use Addons\CatminBooking\Models\BookingService;
use Addons\CatminBooking\Services\BookingAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingServiceController extends Controller
{
    public function __construct(private readonly BookingAdminService $service)
    {
    }

    public function index(Request $request): View
    {
        return view()->file(base_path('addons/catmin-booking/Views/services/index.blade.php'), [
            'currentPage' => 'booking',
            'services' => $this->service->services($request->only(['q'])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer_before_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'buffer_after_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->service->createService($validated);

        return redirect()->route('admin.booking.services.index')
            ->with('status', 'Service de réservation créé.');
    }

    public function edit(BookingService $bookingService): View
    {
        return view()->file(base_path('addons/catmin-booking/Views/services/edit.blade.php'), [
            'currentPage' => 'booking',
            'serviceItem' => $bookingService,
        ]);
    }

    public function update(Request $request, BookingService $bookingService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer_before_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'buffer_after_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->service->updateService($bookingService, $validated);

        return redirect()->route('admin.booking.services.edit', $bookingService->id)
            ->with('status', 'Service mis à jour.');
    }

    public function destroy(BookingService $bookingService): RedirectResponse
    {
        $this->service->deleteService($bookingService);

        return redirect()->route('admin.booking.services.index')
            ->with('status', 'Service supprimé.');
    }
}
