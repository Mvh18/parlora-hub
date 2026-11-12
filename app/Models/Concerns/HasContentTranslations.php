<?php

namespace App\Models\Concerns;

use App\Rules\TranslatedText;
use Spatie\Translatable\HasTranslations;

/**
 * Per-locale content values (`{"en": "...", "es": "..."}`) stored in jsonb.
 *
 * On top of spatie/laravel-translatable, every save checks each translatable
 * attribute with `TranslatedText`: English is required and other locales must
 * be listed in `hub.content_locales`. A model lists the attributes it declares
 * in `$translatable`, and those that may be left empty in
 * `optionalTranslations()`.
 */
trait HasContentTranslations
{
    use HasTranslations;

    protected static function bootHasContentTranslations(): void
    {
        static::saving(function (self $model): void {
            foreach ($model->getTranslatableAttributes() as $attribute) {
                $translations = $model->getTranslations($attribute);

                if ($translations === [] && in_array($attribute, $model->optionalTranslations(), true)) {
                    continue;
                }

                TranslatedText::assert($attribute, $translations);
            }
        });
    }

    /**
     * Translatable attributes that may be absent. Everything else needs at
     * least an English text.
     *
     * @return list<string>
     */
    protected function optionalTranslations(): array
    {
        return [];
    }
}
