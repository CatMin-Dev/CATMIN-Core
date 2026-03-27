<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $table = 'blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'published_at',
        'media_asset_id',
        'seo_meta_id',
        'taxonomy_snapshot',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'media_asset_id' => 'integer',
        'seo_meta_id' => 'integer',
        'taxonomy_snapshot' => 'array',
    ];
}
