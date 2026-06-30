<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * A block type a theme exposes: a slug (`type`), a human label, and an ordered
 * list of {@see FieldSchema} fields. The theme ships a matching Blade partial
 * at `theme::blocks.<type>`; {@see BlockRenderer} renders stored data through it.
 */
class BlockDefinition
{
    /** @param FieldSchema[] $fields */
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly array $fields,
        public readonly ?string $icon = null,
    ) {}

    public static function fromArray(string $type, array $d): self
    {
        $fields = [];
        foreach ($d['fields'] ?? [] as $f) {
            $fields[] = FieldSchema::fromArray($f);
        }

        return new self(
            type: $type,
            label: $d['label'] ?? Str::headline($type),
            fields: $fields,
            icon: $d['icon'] ?? null,
        );
    }

    /** Fresh data for a newly added block, keyed by field. */
    public function defaultData(): array
    {
        $data = [];
        foreach ($this->fields as $field) {
            $data[$field->key] = $field->defaultValue();
        }

        return $data;
    }

    public function field(string $key): ?FieldSchema
    {
        foreach ($this->fields as $field) {
            if ($field->key === $key) {
                return $field;
            }
        }

        return null;
    }
}
