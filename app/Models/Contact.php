<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = ['name', 'phone', 'opted_in', 'last_visit', 'notes'];

    protected $casts = [
        'opted_in' => 'boolean',
        'last_visit' => 'date',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
