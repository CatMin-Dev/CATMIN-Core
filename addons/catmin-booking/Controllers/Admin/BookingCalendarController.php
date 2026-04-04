<?php

namespace Addons\CatminBooking\Controllers\Admin;

use Addons\CatminBooking\Models\BookingService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BookingCalendarController extends Controller
{
    public function index(): View
    {
        return view()->file(base_path('addons/catmin-booking/Views/calendar/index.blade.php'), [
            'currentPage' => 'booking',
            'servicesList' => BookingService::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
