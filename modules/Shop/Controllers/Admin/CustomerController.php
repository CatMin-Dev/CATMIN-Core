<?php

namespace Modules\Shop\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Shop\Models\Customer;
use Modules\Shop\Services\ShopAdminService;

class CustomerController extends Controller
{
    public function __construct(private readonly ShopAdminService $shopAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Shop/Views/customers/index.blade.php'), [
            'currentPage' => 'shop',
            'customers' => $this->shopAdminService->customers(),
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders.items', 'invoices']);

        return view()->file(base_path('modules/Shop/Views/customers/show.blade.php'), [
            'currentPage' => 'shop',
            'customer' => $customer,
        ]);
    }
}
