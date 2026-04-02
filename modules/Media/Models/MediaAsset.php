<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    use SoftDeletes;

    protected $table = 'media_assets';

    protected $fillable = [
        'disk',
        'path',
        'filename',
        'original_name',
        'mime_type',
        'real_mime',
        'extension',
        'size_bytes',
        'alt_text',
        'caption',
        'metadata',
        'uploaded_by_id',
        'quarantine_at',
        'quarantine_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'deleted_at' => 'datetime',
        'quarantine_at' => 'datetime',
        'size_bytes' => 'integer',
        'uploaded_by_id' => 'integer',
    ];

    public function isQuarantined(): bool
    {
        return $this->quarantine_at !== null;
    }
}
