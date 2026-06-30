<?php

namespace App\Support;

use InvalidArgumentException;

class ThemeManifest
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $version,
        public readonly ?string $author,
        public readonly ?string $screenshot,
        public readonly array $supports,
        public readonly array $settings,
        public readonly array $locations,
    ) {}

    public static function fromArray(array $data): self
    {
        foreach (['slug', 'name'] as $required) {
            if (empty($data[$required])) {
                throw new InvalidArgumentException("theme.json missing required field: {$required}");
            }
        }

        return new self(
            slug: $data['slug'],
            name: $data['name'],
            version: $data['version'] ?? '1.0.0',
            author: $data['author'] ?? null,
            screenshot: $data['screenshot'] ?? null,
            supports: $data['supports'] ?? ['content'],
            settings: $data['settings'] ?? [],
            locations: $data['locations'] ?? [],
        );
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new InvalidArgumentException('theme.json is not valid JSON');
        }

        return static::fromArray($data);
    }

    public static function fromPath(string $themePath): self
    {
        $manifestPath = rtrim($themePath, '/').'/theme.json';

        if (! file_exists($manifestPath)) {
            throw new InvalidArgumentException("theme.json not found at: {$manifestPath}");
        }

        return static::fromJson(file_get_contents($manifestPath));
    }
}
