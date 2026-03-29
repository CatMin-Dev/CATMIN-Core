<?php

namespace App\Http\Controllers\Api\V1;

use Modules\Media\Models\MediaAsset;

class MediaAssetsController extends AbstractCrudController
{
    protected string $modelClass = MediaAsset::class;

    protected string $resource = 'media';

    protected array $fillable = [
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

    protected array $searchable = ['path', 'filename', 'original_name', 'mime_type', 'extension', 'alt_text'];

    protected array $filterable = ['disk', 'mime_type', 'extension'];

    protected array $sortable = ['id', 'filename', 'size_bytes', 'created_at', 'updated_at'];

    protected array $webhookEvents = [
        'created' => 'media.created',
        'updated' => 'media.updated',
        'deleted' => 'media.deleted',
    ];
}
