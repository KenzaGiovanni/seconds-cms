<?php

namespace App\Models;

class Page extends Content
{
    public const TYPE = 'page';

    protected static function booted(): void
    {
        static::addGlobalScope('page', fn ($query) => $query->where('type', self::TYPE));
        static::creating(fn (Page $page) => $page->type = self::TYPE);
    }
}
