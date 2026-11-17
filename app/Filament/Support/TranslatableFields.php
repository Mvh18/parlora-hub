<?php

namespace App\Filament\Support;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Support\Str;
use Locale;

/**
 * One input per locale in `hub.content_locales` for a translatable attribute,
 * bound to `<attribute>.<locale>`.
 *
 * English carries `required` when the attribute is required; other locales are
 * always optional. Saving assigns the per-locale array to the model, which
 * replaces the stored translations and drops empty locales
 * (HasContentTranslations), so clearing an optional locale removes it.
 */
final class TranslatableFields
{
    public static function make(string $attribute, bool $required = false, bool $markdown = false, ?string $label = null): Fieldset
    {
        return Fieldset::make($label ?? Str::headline($attribute))
            ->schema(self::fields($attribute, $required, $markdown))
            ->columns(1);
    }

    /**
     * @return list<Textarea|MarkdownEditor>
     */
    public static function fields(string $attribute, bool $required = false, bool $markdown = false): array
    {
        $fields = [];

        foreach ((array) config('hub.content_locales') as $locale) {
            $locale = (string) $locale;
            $name = "{$attribute}.{$locale}";

            $field = $markdown ? MarkdownEditor::make($name) : Textarea::make($name)->rows(2);

            $fields[] = $field
                ->label(Locale::getDisplayLanguage($locale, 'en') ?: $locale)
                ->required($required && $locale === 'en');
        }

        return $fields;
    }
}
