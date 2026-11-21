<?php

namespace App\Content;

use InvalidArgumentException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

/**
 * Validates decoded content files against the JSON Schema files shipped in
 * `resources/schemas/content/`. A thin wrapper around opis/json-schema, so
 * callers never depend on the library and get plain errors keyed by JSON
 * pointer.
 */
final class SchemaValidator
{
    private const BASE_URI = 'https://schemas.parlora.invalid/content/';

    /** The file kinds that have their own schema. */
    public const KINDS = ['level', 'unit', 'exam', 'passage'];

    private const MAX_ERRORS = 200;

    private readonly Validator $validator;

    private readonly ErrorFormatter $formatter;

    public function __construct(?string $schemaDirectory = null)
    {
        $directory = $schemaDirectory ?? resource_path('schemas/content');

        $this->validator = new Validator(max_errors: self::MAX_ERRORS);
        $this->formatter = new ErrorFormatter;

        $resolver = $this->validator->resolver();

        foreach ([...self::KINDS, 'defs'] as $name) {
            $resolver?->registerFile(self::BASE_URI."{$name}.schema.json", "{$directory}/{$name}.schema.json");
        }
    }

    /**
     * The JSON Schema file for a kind, e.g. for editor hints.
     */
    public static function schemaFile(string $kind): string
    {
        return resource_path("schemas/content/{$kind}.schema.json");
    }

    /**
     * @param  string  $kind  one of KINDS
     * @param  mixed  $data  the document decoded with objects (`json_decode($json)`), not associative arrays
     * @return list<SchemaError> empty when the document is valid
     */
    public function validate(string $kind, mixed $data): array
    {
        if (! in_array($kind, self::KINDS, true)) {
            throw new InvalidArgumentException("Unknown content file kind [{$kind}].");
        }

        $result = $this->validator->validate($data, self::BASE_URI."{$kind}.schema.json");

        $error = $result->error();

        if ($error === null) {
            return [];
        }

        $errors = [];

        foreach ($this->leaves($error) as $leaf) {
            foreach ($this->describe($leaf) as $described) {
                $errors[$described->pointer."\0".$described->message] = $described;
            }
        }

        return array_values($errors);
    }

    /**
     * Validate a document held as nested arrays (factory states, tests).
     *
     * @param  array<array-key, mixed>  $data
     * @return list<SchemaError>
     */
    public function validateArray(string $kind, array $data): array
    {
        return $this->validate($kind, json_decode(json_encode($data, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR));
    }

    /** @return iterable<ValidationError> */
    private function leaves(ValidationError $error): iterable
    {
        $sub = $error->subErrors();

        if ($sub === []) {
            yield $error;

            return;
        }

        foreach ($sub as $child) {
            yield from $this->leaves($child);
        }
    }

    /** @return list<SchemaError> */
    private function describe(ValidationError $error): array
    {
        $base = self::pointer($error->data()->fullPath());
        $properties = $error->args()['properties'] ?? null;

        if (in_array($error->keyword(), ['additionalProperties', 'unevaluatedProperties'], true) && is_array($properties)) {
            return array_map(
                fn ($property): SchemaError => new SchemaError(
                    $base.'/'.self::escape((string) $property),
                    'This member is not allowed here.',
                ),
                array_values($properties),
            );
        }

        return [new SchemaError($base, $this->formatter->formatErrorMessage($error))];
    }

    /** @param  array<int, int|string>  $segments */
    private static function pointer(array $segments): string
    {
        $pointer = '';

        foreach ($segments as $segment) {
            $pointer .= '/'.self::escape((string) $segment);
        }

        return $pointer;
    }

    private static function escape(string $segment): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $segment);
    }
}
