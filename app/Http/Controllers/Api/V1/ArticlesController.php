<?php

namespace App\Http\Controllers\Api\V1;

use Modules\Articles\Models\Article;

class ArticlesController extends AbstractCrudController
{
    protected string $modelClass = Article::class;

    protected string $resource = 'articles';

    protected array $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'content_type',
        'status',
        'published_at',
        'taxonomy_snapshot',
    ];

    protected array $searchable = ['title', 'slug', 'excerpt', 'content'];

    protected array $filterable = ['status', 'content_type', 'slug'];

    protected array $sortable = ['id', 'title', 'slug', 'status', 'content_type', 'published_at', 'created_at', 'updated_at'];

    protected array $webhookEvents = [
        'created' => 'article.created',
        'updated' => 'article.updated',
        'deleted' => 'article.deleted',
    ];
}
