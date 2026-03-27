<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;

class MailerTemplate extends Model
{
    protected $table = 'mailer_templates';

    protected $fillable = [
        'code',
        'name',
        'description',
        'subject',
        'body_html',
        'body_text',
        'available_variables',
        'sample_payload',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'sample_payload' => 'array',
            'is_enabled' => 'boolean',
        ];
    }
}
