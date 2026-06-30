<?php

namespace App\Models;

use App\Support\FieldSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'fields',
        'recipient_email',
        'success_message',
        'settings',
    ];

    protected $casts = [
        'fields' => 'array',
        'settings' => 'array',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /** @return FieldSchema[] */
    public function fieldSchemas(): array
    {
        return array_map(
            fn (array $field) => FieldSchema::fromArray($field),
            $this->fields ?? [],
        );
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
