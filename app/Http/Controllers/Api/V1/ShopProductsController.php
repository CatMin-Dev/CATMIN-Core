<?php

namespace App\Http\Controllers\Api\V1;

use Modules\Shop\Models\Product;

class ShopProductsController extends AbstractCrudController
{
    protected string $modelClass = Product::class;

    protected string $resource = 'shop_products';

    protected array $fillable = [
        'name',
        'slug',
        'sku',
        'price',
        'compare_at_price',
        'description',
        'stock_quantity',
        'low_stock_threshold',
        'status',
        'visibility',
        'manage_stock',
        'image_path',
        'product_type',
        'published_at',
    ];

    protected array $searchable = ['name', 'slug', 'sku', 'description'];

    protected array $filterable = ['status', 'visibility', 'slug'];

    protected array $sortable = ['id', 'name', 'slug', 'price', 'status', 'published_at', 'created_at', 'updated_at'];

    protected array $webhookEvents = [
        'created' => 'shop.product.created',
        'updated' => 'shop.product.updated',
        'deleted' => 'shop.product.deleted',
    ];
}
