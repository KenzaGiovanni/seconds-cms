<?php

namespace App\Models;

class Post extends Content
{
    public const TYPE = 'post';

    protected static function booted(): void
    {
        static::addGlobalScope('post', fn ($query) => $query->where('type', self::TYPE));
        static::creating(fn (Post $post) => $post->type = self::TYPE);
    }
}
