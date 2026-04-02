<?php

namespace Addons\CatminBooking\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BookingCalendarController extends Controller
{
    public function index(): View
    {
        return view()->file(base_path('addons/catmin-booking/Views/calendar/index.blade.php'), [
            'currentPage' => 'booking',
        ]);
    }
}
