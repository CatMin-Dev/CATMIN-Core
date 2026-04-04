<?php

namespace Addons\CatminCrmLight\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCompany extends Model
{
    protected $table = 'crm_companies';

    protected $fillable = [
        'name',
        'website',
        'industry',
        'email',
        'phone',
        'address',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CrmContact::class, 'crm_company_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CrmInteraction::class, 'crm_company_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'crm_company_id');
    }
}
