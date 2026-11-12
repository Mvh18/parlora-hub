<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * A per-locale text such as `{"en": "to look for", "es": "buscar"}`.
 *
 * English is required; other locales are optional and limited to
 * `hub.content_locales`. Used as a validation rule (errors land on the
 * attribute) and through `assert()`, which models call on save and whose
 * errors name the failing locale (`meaning.en`, `theory.fr`).
 */
final class TranslatedText implements ValidationRule
{
    /**
     * Problems with the value, keyed by the locale they concern, or '' for
     * the value as a whole. Empty when the value is valid.
     *
     * @return array<string, string>
     */
    public static function errors(mixed $value, string $attribute = 'value'): array
    {
        if (! is_array($value)) {
            return ['' => trans('validation.content.translated.not_array', ['attribute' => $attribute])];
        }

        $errors = [];

        $english = $value['en'] ?? null;
        if (! is_string($english) || trim($english) === '') {
            $errors['en'] = trans('validation.content.translated.english_required', ['attribute' => $attribute]);
        }

        $allowed = (array) config('hub.content_locales');

        foreach ($value as $locale => $text) {
            $locale = (string) $locale;

            if (! in_array($locale, $allowed, true)) {
                $errors[$locale] = trans('validation.content.translated.locale_unsupported', ['attribute' => $attribute, 'locale' => $locale]);
            } elseif ($locale !== 'en' && (! is_string($text) || trim($text) === '')) {
                $errors[$locale] = trans('validation.content.translated.not_text', ['attribute' => $attribute, 'locale' => $locale]);
            }
        }

        return $errors;
    }

    /**
     * @throws ValidationException naming `<attribute>.<locale>` for each problem
     */
    public static function assert(string $attribute, mixed $value): void
    {
        $errors = self::errors($value, $attribute);

        if ($errors === []) {
            return;
        }

        $keyed = [];
        foreach ($errors as $locale => $message) {
            $keyed[$locale === '' ? $attribute : "{$attribute}.{$locale}"] = [$message];
        }

        throw ValidationException::withMessages($keyed);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (self::errors($value, $attribute) as $message) {
            $fail($message);
        }
    }
}
