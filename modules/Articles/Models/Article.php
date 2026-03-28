<?php

namespace Modules\Articles\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'articles';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'content_type',
        'status',
        'published_at',
        'media_asset_id',
        'seo_meta_id',
        'meta_title',
        'meta_description',
        'taxonomy_snapshot',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'media_asset_id' => 'integer',
        'seo_meta_id' => 'integer',
        'taxonomy_snapshot' => 'array',
    ];
}
