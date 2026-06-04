<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segment extends Model
{
    protected $fillable = ['name', 'description', 'conditions'];

    protected $casts = [
        'conditions' => 'array',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
