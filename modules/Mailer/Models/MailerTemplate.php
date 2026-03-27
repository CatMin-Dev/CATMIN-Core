<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;

class MailerTemplate extends Model
{
    protected $table = 'mailer_templates';

    protected $fillable = [
        'code',
        'name',
        'subject',
        'body_html',
        'body_text',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
