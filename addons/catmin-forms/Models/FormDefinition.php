<?php

namespace Addons\CatminForms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormDefinition extends Model
{
    protected $table = 'form_definitions';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_definition_id')->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_definition_id')->orderByDesc('created_at');
    }
}
