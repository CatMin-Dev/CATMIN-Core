<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $table = 'media_assets';

    protected $fillable = [
        'disk',
        'path',
        'filename',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'alt_text',
        'caption',
        'metadata',
        'uploaded_by_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size_bytes' => 'integer',
        'uploaded_by_id' => 'integer',
    ];
}
