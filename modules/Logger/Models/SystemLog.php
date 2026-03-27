<?php

namespace Modules\Logger\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';

    protected $fillable = [
        'channel',
        'level',
        'event',
        'message',
        'context',
        'admin_username',
        'method',
        'url',
        'ip_address',
        'status_code',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'status_code' => 'integer',
        ];
    }
}
