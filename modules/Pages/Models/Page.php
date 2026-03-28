<?php

namespace Modules\Pages\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'published_at',
        'media_asset_id',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'media_asset_id' => 'integer',
    ];
}
