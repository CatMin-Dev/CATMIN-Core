<?php

namespace Modules\SEO\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'target_type',
        'target_id',
        'meta_title',
        'meta_description',
        'meta_robots',
        'canonical_url',
        'slug_override',
    ];

    protected $casts = [
        'target_id' => 'integer',
    ];
}
