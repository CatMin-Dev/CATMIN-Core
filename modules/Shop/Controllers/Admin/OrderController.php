<?php

namespace Modules\Shop\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\ShopAdminService;

class OrderController extends Controller
{
    public function __construct(private readonly ShopAdminService $shopAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Shop/Views/orders/index.blade.php'), [
            'currentPage' => 'shop',
            'orders' => $this->shopAdminService->orders(),
            'statuses' => $this->shopAdminService->orderStatuses(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Shop/Views/orders/create.blade.php'), [
            'currentPage' => 'shop',
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
            'statuses' => ['pending', 'paid'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:191'],
            'customer_email' => ['required', 'email', 'max:191'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['pending', 'paid'])],
            'currency' => ['required', 'string', 'size:3'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'admin_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:shop_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $order = $this->shopAdminService->createOrder($validated);

        return redirect()->route('admin.shop.orders.show', ['order' => $order->id])->with('status', 'Commande creee.');
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'invoice']);

        return view()->file(base_path('modules/Shop/Views/orders/show.blade.php'), [
            'currentPage' => 'shop',
            'order' => $order,
            'allowedTransitions' => $this->shopAdminService->allowedTransitions($order->status),
        ]);
    }

    public function transition(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        if (!$this->shopAdminService->transitionOrder($order, (string) $validated['status'])) {
            return redirect()->route('admin.shop.orders.show', ['order' => $order->id])->with('error', 'Transition de statut non autorisee.');
        }

        return redirect()->route('admin.shop.orders.show', ['order' => $order->id])->with('status', 'Statut commande mis a jour.');
    }
}
