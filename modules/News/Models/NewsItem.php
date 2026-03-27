<?php

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Model;

class NewsItem extends Model
{
    protected $table = 'news_items';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'status',
        'published_at',
        'media_asset_id',
        'seo_meta_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'media_asset_id' => 'integer',
        'seo_meta_id' => 'integer',
    ];
}
