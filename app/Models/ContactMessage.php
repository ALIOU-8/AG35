<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use EncryptsRouteKey;

    protected $fillable = ['name', 'email', 'subject', 'body'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
