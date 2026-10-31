<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PostMedia extends Model
{
    protected $fillable = ['type', 'path', 'mime', 'size', 'position'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }
}
