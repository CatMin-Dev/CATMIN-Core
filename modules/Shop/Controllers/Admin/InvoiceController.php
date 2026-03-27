<?php

namespace Modules\Shop\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Shop\Models\Invoice;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice): View
    {
        $invoice->load(['order.items', 'customer']);

        return view()->file(base_path('modules/Shop/Views/invoices/show.blade.php'), [
            'currentPage' => 'shop',
            'invoice' => $invoice,
            'order' => $invoice->order,
            'customer' => $invoice->customer,
            'renderMode' => 'page',
        ]);
    }
}
