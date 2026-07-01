<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'url',
        'linkable_type', 'linkable_id', 'sort_order',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Resolved URL: linked content URL or custom URL. */
    public function resolvedUrl(): string
    {
        if ($this->linkable) {
            $model = $this->linkable;
            if ($model instanceof Post) {
                return route('blog.show', $model->slug);
            }
            if ($model instanceof Page) {
                return route('content.show', $model->slug);
            }
        }

        return $this->url ?? '#';
    }
}
