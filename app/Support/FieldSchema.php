<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * One editable field in a block or form definition. Themes declare these in
 * their `blocks.php`; the admin generates an input from the `type`, and the
 * front-end reads the stored value back out by `key`.
 *
 * Supported types: text, textarea, richtext, image, number, toggle, select,
 * repeater. A `repeater` carries its own nested `fields` (sub-schema).
 */
class FieldSchema
{
    public const TYPES = ['text', 'textarea', 'richtext', 'image', 'number', 'toggle', 'select', 'repeater'];

    /**
     * @param  array<string, string>  $options  value => label, for `select`
     * @param  FieldSchema[]  $fields  sub-fields, for `repeater`
     */
    public function __construct(
        public readonly string $key,
        public readonly string $type,
        public readonly string $label,
        public readonly mixed $default = null,
        public readonly array $options = [],
        public readonly array $fields = [],
        public readonly bool $required = false,
        public readonly ?string $help = null,
    ) {}

    public static function fromArray(array $d): self
    {
        $type = in_array($d['type'] ?? null, self::TYPES, true) ? $d['type'] : 'text';

        $sub = [];
        if ($type === 'repeater') {
            foreach ($d['fields'] ?? [] as $f) {
                $sub[] = self::fromArray($f);
            }
        }

        return new self(
            key: $d['key'],
            type: $type,
            label: $d['label'] ?? Str::headline($d['key']),
            default: $d['default'] ?? self::defaultFor($type),
            options: $d['options'] ?? [],
            fields: $sub,
            required: (bool) ($d['required'] ?? false),
            help: $d['help'] ?? null,
        );
    }

    /** The empty/initial value for a given field type. */
    public static function defaultFor(string $type): mixed
    {
        return match ($type) {
            'toggle' => false,
            'number' => null,
            'repeater' => [],
            default => '',
        };
    }

    /** The value to seed when a fresh block/item is added. */
    public function defaultValue(): mixed
    {
        return $this->type === 'repeater' ? [] : $this->default;
    }

    /** Default data for one repeater row, keyed by sub-field. */
    public function rowTemplate(): array
    {
        $row = [];
        foreach ($this->fields as $f) {
            $row[$f->key] = $f->defaultValue();
        }

        return $row;
    }
}
