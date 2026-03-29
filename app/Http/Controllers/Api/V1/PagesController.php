<?php

namespace App\Http\Controllers\Api\V1;

use Modules\Pages\Models\Page;

class PagesController extends AbstractCrudController
{
    protected string $modelClass = Page::class;

    protected string $resource = 'pages';

    protected array $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'published_at',
    ];

    protected array $searchable = ['title', 'slug', 'content'];

    protected array $filterable = ['status', 'slug'];

    protected array $sortable = ['id', 'title', 'slug', 'status', 'published_at', 'created_at', 'updated_at'];

    protected array $webhookEvents = [
        'created' => 'page.created',
        'updated' => 'page.updated',
        'deleted' => 'page.deleted',
    ];
}
