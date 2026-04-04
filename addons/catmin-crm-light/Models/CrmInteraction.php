<?php

namespace Addons\CatminCrmLight\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmInteraction extends Model
{
    protected $table = 'crm_interactions';

    protected $fillable = [
        'crm_contact_id',
        'crm_company_id',
        'type',
        'subject',
        'content',
        'source',
        'happened_at',
        'created_by_id',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'crm_company_id');
    }
}
