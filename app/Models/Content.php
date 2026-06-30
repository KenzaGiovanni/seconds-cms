<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Base content model. Pages and posts share this one table (`contents`) and are
 * distinguished by the `type` discriminator (STI-lite). Use the Page / Post
 * subclasses for type-scoped queries; use Content directly for cross-type reads
 * (e.g. front-end slug resolution).
 */
class Content extends Model
{
    protected $table = 'contents';

    protected $fillable = [
        'type', 'title', 'slug', 'status', 'body', 'blocks',
        'excerpt', 'featured_image_id', 'author_id', 'published_at', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'status' => ContentStatus::class,
        'blocks' => 'array',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function categories(): BelongsToMany
    {
        // Explicit keys required: Post subclass would otherwise infer 'post_id' as the FK.
        return $this->belongsToMany(Category::class, 'category_content', 'content_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'content_tag', 'content_id', 'tag_id');
    }

    /** Published = status published AND publish time has arrived. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
