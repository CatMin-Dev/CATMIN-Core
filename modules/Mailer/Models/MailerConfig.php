<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;

class MailerConfig extends Model
{
    protected $table = 'mailer_configs';

    protected $fillable = [
        'driver',
        'from_email',
        'from_name',
        'reply_to_email',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
