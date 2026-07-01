<?php

namespace App\Enums;

/**
 * Publish state for a product. Kept separate from content's ContentStatus so the
 * catalog can evolve independently (e.g. archived/out-of-catalog states later).
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
