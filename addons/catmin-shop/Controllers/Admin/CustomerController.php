<?php

namespace Addons\CatminShop\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Addons\CatminShop\Models\Customer;
use Addons\CatminShop\Services\ShopAdminService;

class CustomerController extends Controller
{
    public function __construct(private readonly ShopAdminService $shopAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('addons/catmin-shop/Views/customers/index.blade.php'), [
            'currentPage' => 'shop',
            'customers' => $this->shopAdminService->customers(),
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders.items', 'invoices']);

        return view()->file(base_path('addons/catmin-shop/Views/customers/show.blade.php'), [
            'currentPage' => 'shop',
            'customer' => $customer,
        ]);
    }
}
