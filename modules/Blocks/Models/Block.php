<?php

namespace Modules\Blocks\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $table = 'blocks';

    protected $fillable = [
        'name',
        'slug',
        'content',
        'status',
    ];
}
