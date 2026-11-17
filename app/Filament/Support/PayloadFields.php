<?php

namespace App\Filament\Support;

use App\Data\Exercises\ExercisePayloads;
use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;
use Closure;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use JsonException;

/**
 * JSON code editors for an exercise's or exam question's `content` and
 * `solution`, validated with the exercise payload contract before saving.
 *
 * Each editor reports its own malformed JSON. When both decode, the contract
 * runs with the form's `type`, and each error is shown on the editor of its
 * side with its path (`solution.correct.0: …`). The model's `saving` hook still
 * validates, so the form cannot bypass the contract.
 */
final class PayloadFields
{
    public const SIDES = ['content', 'solution'];

    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * @return list<CodeEditor>
     */
    public static function make(string $typeField = 'type'): array
    {
        return array_map(fn (string $side): CodeEditor => self::editor($side, $typeField), self::SIDES);
    }

    private static function editor(string $side, string $typeField): CodeEditor
    {
        return CodeEditor::make($side)
            ->language(Language::Json)
            ->required()
            ->afterStateHydrated(function (CodeEditor $component, ?Model $record) use ($side): void {
                $component->state(self::storedJson($record, $side));
            })
            ->dehydrateStateUsing(fn (?string $state): ?array => self::decodeQuietly($state))
            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $side, $typeField): void {
                foreach (self::errors($side, $value, $get(self::otherSide($side)), $get($typeField)) as $message) {
                    $fail($message);
                }
            });
    }

    /**
     * Problems to show on the `$side` editor; empty when it is valid.
     *
     * @return list<string>
     */
    public static function errors(string $side, mixed $json, mixed $otherJson, mixed $type): array
    {
        try {
            $payload = self::decodeObject($json);
        } catch (JsonException $exception) {
            return [trans('validation.content.payload.json_invalid', ['attribute' => $side, 'error' => $exception->getMessage()])];
        }

        if ($payload === null) {
            return [trans('validation.content.payload.json_object', ['attribute' => $side])];
        }

        $other = self::decodeQuietly($otherJson);
        $type = $type instanceof ExerciseType ? $type : ExerciseType::tryFrom((string) $type);

        // The other editor reports its own JSON problems; the type select its own.
        if ($other === null || $type === null) {
            return [];
        }

        [$content, $solution] = $side === 'content' ? [$payload, $other] : [$other, $payload];

        try {
            ExercisePayloads::validate($type, $content, $solution);
        } catch (InvalidExercisePayload $exception) {
            $messages = [];

            foreach ($exception->errors() as $path => $pathMessages) {
                if ($path === $side || str_starts_with($path, "{$side}.")) {
                    $messages[] = trans('validation.content.payload.at_path', ['path' => $path, 'message' => $pathMessages[0]]);
                }
            }

            return $messages;
        }

        return [];
    }

    private static function otherSide(string $side): string
    {
        return $side === 'content' ? 'solution' : 'content';
    }

    private static function storedJson(?Model $record, string $side): ?string
    {
        $raw = $record?->getRawOriginal($side);

        if (! is_string($raw)) {
            return null;
        }

        return json_encode(json_decode($raw, true), self::JSON_FLAGS) ?: null;
    }

    /** @return array<string, mixed>|null */
    private static function decodeQuietly(mixed $json): ?array
    {
        try {
            return self::decodeObject($json);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * A JSON object as an array, or null when the JSON is another kind of value.
     *
     * @return array<string, mixed>|null
     *
     * @throws JsonException when the text is not JSON at all
     */
    private static function decodeObject(mixed $json): ?array
    {
        $value = json_decode(is_string($json) ? $json : '', false, flags: JSON_THROW_ON_ERROR);

        if (! is_object($value)) {
            return null;
        }

        /** @var array<string, mixed> */
        return json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);
    }
}
