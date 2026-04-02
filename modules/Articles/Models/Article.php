<?php

namespace Modules\Articles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    protected $table = 'articles';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'content_type',
        'article_category_id',
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
        'deleted_at' => 'datetime',
        'article_category_id' => 'integer',
        'media_asset_id' => 'integer',
        'seo_meta_id' => 'integer',
        'taxonomy_snapshot' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tag', 'article_id', 'tag_id')->withTimestamps();
    }
}
