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
        'brand_name',
        'brand_logo_url',
        'brand_primary_color',
        'brand_footer_text',
        'sandbox_mode',
        'sandbox_recipient',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'sandbox_mode' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }
}
