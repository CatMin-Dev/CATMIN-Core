<?php

namespace Addons\CatminProfileExtensions\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfileExtended extends Model
{
    protected $table = 'user_profiles_extended';

    protected $fillable = [
        'user_id',
        'admin_user_id',
        'phone',
        'mobile',
        'company_name',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'state',
        'country_code',
        'identity_type',
        'identity_number',
        'preferred_contact_method',
        'contact_opt_in',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'admin_user_id' => 'integer',
        'contact_opt_in' => 'boolean',
    ];
}
