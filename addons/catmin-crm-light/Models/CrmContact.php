<?php

namespace Addons\CatminCrmLight\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmContact extends Model
{
    protected $table = 'crm_contacts';

    protected $fillable = [
        'crm_company_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'status',
        'pipeline_stage',
        'source',
        'tags',
        'notes',
        'last_interaction_at',
        'metadata',
    ];

    protected $casts = [
        'last_interaction_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'crm_company_id');
    }

    public function crmNotes(): HasMany
    {
        return $this->hasMany(CrmNote::class, 'crm_contact_id')->orderByDesc('created_at');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CrmInteraction::class, 'crm_contact_id')->orderByDesc('happened_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'crm_contact_id')->orderByDesc('created_at');
    }

    public function fullName(): string
    {
        return trim((string) $this->first_name . ' ' . (string) ($this->last_name ?? ''));
    }
}
