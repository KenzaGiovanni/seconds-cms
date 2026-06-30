<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = ['slug', 'name', 'version', 'author', 'status', 'settings', 'screenshot', 'installed_at'];

    protected $casts = [
        'settings' => 'array',
        'installed_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function active(): ?self
    {
        return static::where('status', 'active')->first();
    }
}
