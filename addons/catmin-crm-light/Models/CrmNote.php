<?php

namespace Addons\CatminCrmLight\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmNote extends Model
{
    protected $table = 'crm_notes';

    protected $fillable = [
        'crm_contact_id',
        'type',
        'content',
        'module',
        'linked_type',
        'linked_id',
        'created_by_id',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }
}
