<?php

namespace Addons\CatminShop\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Logger\Services\SystemLogService;
use Modules\Mailer\Services\MailerAdminService;
use Addons\CatminShop\Models\Category;
use Addons\CatminShop\Models\Customer;
use Addons\CatminShop\Models\Invoice;
use Addons\CatminShop\Models\Order;
use Addons\CatminShop\Models\OrderItem;
use Addons\CatminShop\Models\Product;

class ShopAdminService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function listing(array $filters = []): LengthAwarePaginator
    {
        return Product::query()
            ->with('categories')
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('status', (string) $filters['status']))
            ->when(($filters['category_id'] ?? '') !== '', fn ($query) => $query->whereHas('categories', fn ($q) => $q->where('shop_categories.id', (int) $filters['category_id'])))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
    }

    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::query()->with('parent')->orderBy('sort_order')->orderBy('name')->get();
    }

    public function activeCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function customers(): LengthAwarePaginator
    {
        return Customer::query()->withCount('orders')->orderByDesc('id')->paginate(25);
    }

    public function orders(): LengthAwarePaginator
    {
        return Order::query()->with(['customer', 'items'])->orderByDesc('id')->paginate(25);
    }

    public function orderStatuses(): array
    {
        return ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];
    }

    public function visibilityOptions(): array
    {
        return ['public', 'catalog_only', 'hidden'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Product
    {
        $slug = $this->uniqueSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''));

        /** @var Product $product */
        $product = DB::transaction(function () use ($payload, $slug) {
            $product = Product::query()->create($this->productPayload($payload, $slug));
            $product->categories()->sync($this->normalizeCategoryIds($payload['category_ids'] ?? []));

            return $product->fresh(['categories']) ?? $product;
        });

        $this->logAudit('shop.product.created', 'Produit shop cree', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'stock_quantity' => $product->stock_quantity,
        ]);

        return $product;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Product $product, array $payload): Product
    {
        $slug = $this->uniqueSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''), $product->id);

        /** @var Product $updated */
        $updated = DB::transaction(function () use ($product, $payload, $slug) {
            $product->fill($this->productPayload($payload, $slug));
            $product->save();
            $product->categories()->sync($this->normalizeCategoryIds($payload['category_ids'] ?? []));

            return $product->fresh(['categories']) ?? $product;
        });

        $this->logAudit('shop.product.updated', 'Produit shop modifie', [
            'product_id' => $updated->id,
            'sku' => $updated->sku,
            'stock_quantity' => $updated->stock_quantity,
        ]);

        return $updated;
    }

    public function toggleStatus(Product $product): Product
    {
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        if ($product->status === 'active' && $product->published_at === null) {
            $product->published_at = now();
        }
        $product->save();

        $this->logAudit('shop.product.status', 'Statut produit shop change', [
            'product_id' => $product->id,
            'status' => $product->status,
        ]);

        return $product;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createCategory(array $payload): Category
    {
        $slug = $this->uniqueCategorySlug((string) $payload['name'], (string) ($payload['slug'] ?? ''));

        $category = Category::query()->create([
            'parent_id' => !empty($payload['parent_id']) ? (int) $payload['parent_id'] : null,
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'description' => (string) ($payload['description'] ?? ''),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $this->logAudit('shop.category.created', 'Categorie shop creee', ['category_id' => $category->id]);

        return $category;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateCategory(Category $category, array $payload): Category
    {
        $slug = $this->uniqueCategorySlug((string) $payload['name'], (string) ($payload['slug'] ?? ''), $category->id);

        $category->fill([
            'parent_id' => !empty($payload['parent_id']) ? (int) $payload['parent_id'] : null,
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'description' => (string) ($payload['description'] ?? ''),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);
        $category->save();

        $this->logAudit('shop.category.updated', 'Categorie shop modifiee', ['category_id' => $category->id]);

        return $category;
    }

    public function deleteCategory(Category $category): bool
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return false;
        }

        $deleted = (bool) $category->delete();

        if ($deleted) {
            $this->logAudit('shop.category.deleted', 'Categorie shop supprimee', ['category_id' => $category->id]);
        }

        return $deleted;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createOrder(array $payload): Order
    {
        /** @var Order $order */
        $order = DB::transaction(function () use ($payload) {
            $customer = $this->resolveCustomer($payload);

            $order = Order::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'customer_id' => $customer?->id,
                'customer_email' => (string) $payload['customer_email'],
                'customer_name' => (string) $payload['customer_name'],
                'status' => (string) ($payload['status'] ?? 'pending'),
                'currency' => (string) ($payload['currency'] ?? 'EUR'),
                'subtotal' => 0,
                'tax_total' => (float) ($payload['tax_total'] ?? 0),
                'shipping_total' => (float) ($payload['shipping_total'] ?? 0),
                'grand_total' => 0,
                'admin_notes' => (string) ($payload['admin_notes'] ?? ''),
            ]);

            $subtotal = 0.0;
            foreach ($this->normalizeOrderItems($payload['items'] ?? []) as $line) {
                /** @var Product|null $product */
                $product = Product::query()->find($line['product_id']);
                if (!$product) {
                    continue;
                }

                $quantity = (int) $line['quantity'];
                $unitPrice = (float) $product->price;
                $lineTotal = $quantity * $unitPrice;
                $subtotal += $lineTotal;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->subtotal = $subtotal;
            $order->grand_total = $subtotal + (float) $order->tax_total + (float) $order->shipping_total;
            $order->save();

            if (in_array($order->status, ['paid', 'processing', 'shipped', 'completed'], true)) {
                $this->decrementStockForOrder($order);
            }

            $invoice = $this->generateInvoice($order);
            $order->setRelation('invoice', $invoice);

            return $order->fresh(['customer', 'items', 'invoice']) ?? $order;
        });

        $this->sendOrderMail($order, 'created');
        $this->logAudit('shop.order.created', 'Commande shop creee', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'grand_total' => $order->grand_total,
        ]);

        return $order;
    }

    public function transitionOrder(Order $order, string $targetStatus): bool
    {
        $allowed = $this->allowedTransitions($order->status);
        if (!in_array($targetStatus, $allowed, true)) {
            return false;
        }

        $decrementStatuses = ['paid', 'processing', 'shipped', 'completed'];
        $shouldDecrementStock = !in_array($order->status, $decrementStatuses, true) && in_array($targetStatus, $decrementStatuses, true);

        $order->status = $targetStatus;
        if ($targetStatus === 'paid') {
            $order->paid_at = now();
        }
        if ($targetStatus === 'shipped') {
            $order->shipped_at = now();
        }
        if ($targetStatus === 'completed') {
            $order->completed_at = now();
        }
        $order->save();

        if ($shouldDecrementStock) {
            $this->decrementStockForOrder($order->fresh('items') ?? $order);
        }

        if (!$order->invoice) {
            $this->generateInvoice($order);
        }

        $this->sendOrderMail($order, 'status:' . $targetStatus);
        $this->logAudit('shop.order.status', 'Statut commande shop modifie', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $targetStatus,
        ]);

        return true;
    }

    public function allowedTransitions(string $status): array
    {
        return match ($status) {
            'pending' => ['paid', 'cancelled'],
            'paid' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['completed'],
            default => [],
        };
    }

    public function generateInvoice(Order $order): Invoice
    {
        $existing = $order->invoice;
        if ($existing) {
            return $existing;
        }

        $invoice = Invoice::query()->create([
            'invoice_number' => $this->nextInvoiceNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'status' => 'issued',
            'currency' => $order->currency,
            'total' => $order->grand_total,
            'issued_on' => now()->toDateString(),
            'due_on' => now()->addDays(15)->toDateString(),
            'html_snapshot' => view()->file(base_path('addons/catmin-shop/Views/invoices/show.blade.php'), [
                'invoice' => null,
                'order' => $order->loadMissing(['items', 'customer']),
                'customer' => $order->customer,
                'renderMode' => 'snapshot',
            ])->render(),
        ]);

        $this->logAudit('shop.invoice.generated', 'Facture shop generee', [
            'invoice_id' => $invoice->id,
            'order_id' => $order->id,
        ]);

        return $invoice;
    }

    private function decrementStockForOrder(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if (!$item->product || !$item->product->manage_stock) {
                continue;
            }

            $item->product->stock_quantity = max(0, (int) $item->product->stock_quantity - (int) $item->quantity);
            $item->product->save();
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function productPayload(array $payload, string $slug): array
    {
        $status = (string) ($payload['status'] ?? 'inactive');

        return [
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'sku' => !empty($payload['sku']) ? (string) $payload['sku'] : null,
            'price' => (float) $payload['price'],
            'compare_at_price' => $payload['compare_at_price'] !== null && $payload['compare_at_price'] !== '' ? (float) $payload['compare_at_price'] : null,
            'description' => (string) ($payload['description'] ?? ''),
            'stock_quantity' => (int) ($payload['stock_quantity'] ?? 0),
            'low_stock_threshold' => (int) ($payload['low_stock_threshold'] ?? 5),
            'status' => $status,
            'visibility' => (string) ($payload['visibility'] ?? 'public'),
            'manage_stock' => (bool) ($payload['manage_stock'] ?? true),
            'image_path' => !empty($payload['image_path']) ? (string) $payload['image_path'] : null,
            'product_type' => (string) ($payload['product_type'] ?? 'physical'),
            'published_at' => $status === 'active' ? now() : null,
        ];
    }

    /**
     * @param mixed $items
     * @return array<int, array<string, int>>
     */
    private function normalizeOrderItems(mixed $items): array
    {
        return collect((array) $items)
            ->map(fn ($line) => [
                'product_id' => (int) ($line['product_id'] ?? 0),
                'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
            ])
            ->filter(fn (array $line) => $line['product_id'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param mixed $categoryIds
     * @return array<int, int>
     */
    private function normalizeCategoryIds(mixed $categoryIds): array
    {
        return collect((array) $categoryIds)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveCustomer(array $payload): ?Customer
    {
        $email = trim((string) ($payload['customer_email'] ?? ''));
        if ($email === '') {
            return null;
        }

        [$firstName, $lastName] = array_pad(explode(' ', trim((string) ($payload['customer_name'] ?? '')), 2), 2, null);

        return Customer::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName ?: $email,
                'last_name' => $lastName,
                'phone' => !empty($payload['customer_phone']) ? (string) $payload['customer_phone'] : null,
            ]
        );
    }

    private function nextOrderNumber(): string
    {
        return 'CMD-' . now()->format('Ymd') . '-' . str_pad((string) ((Order::query()->count() ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function nextInvoiceNumber(): string
    {
        return 'FAC-' . now()->format('Ymd') . '-' . str_pad((string) ((Invoice::query()->count() ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function uniqueCategorySlug(string $name, string $candidateSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidateSlug !== '' ? $candidateSlug : $name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'category';
        $slug = $baseSlug;
        $suffix = 1;

        while (Category::query()->where('slug', $slug)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            ++$suffix;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }

    private function sendOrderMail(Order $order, string $reason): void
    {
        try {
            if ($order->customer_email === '') {
                return;
            }

            $templateCode = str_starts_with($reason, 'status:') ? 'shop_order_status' : 'shop_order_created';

            app(MailerAdminService::class)->dispatchTemplate(
                $templateCode,
                $order->customer_email,
                $order->customer_name,
                [
                    'customer' => [
                        'name' => $order->customer_name,
                        'email' => $order->customer_email,
                    ],
                    'order' => [
                        'number' => $order->order_number,
                        'status' => $order->status,
                        'total' => number_format((float) $order->grand_total, 2, '.', ''),
                        'currency' => $order->currency,
                        'reason' => $reason,
                    ],
                ],
                [
                    'queue' => true,
                    'trigger_source' => 'shop.' . $reason,
                ]
            );

            $this->logAudit('shop.mail.sent', 'Email commande delegue au mailer', [
                'order_id' => $order->id,
                'reason' => $reason,
                'email' => $order->customer_email,
                'template_code' => $templateCode,
            ]);
        } catch (\Throwable $throwable) {
            $this->logAudit('shop.mail.failed', 'Echec delegation email commande', [
                'order_id' => $order->id,
                'reason' => $reason,
                'error' => $throwable->getMessage(),
            ], 'warning');
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logAudit(string $event, string $message, array $context = [], string $level = 'info'): void
    {
        try {
            app(SystemLogService::class)->logAudit(
                $event,
                $message,
                $context,
                $level,
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
        }
    }

    private function uniqueSlug(string $name, string $candidateSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidateSlug !== '' ? $candidateSlug : $name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'product';

        $slug = $baseSlug;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Product::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
