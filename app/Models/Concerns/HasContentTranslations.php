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
    use HasTranslations {
        HasTranslations::setAttribute as setTranslatableAttribute;
    }

    /**
     * Assigning a per-locale array (`['en' => '…', 'es' => '…']`) to a
     * translatable attribute replaces its translations instead of merging into
     * them, and drops locales whose text is empty or null. A form or an import
     * that leaves a locale out therefore removes it rather than keeping a stale
     * text or storing "".
     *
     * @param  string  $key
     */
    public function setAttribute($key, $value): mixed
    {
        if ($this->isTranslatableAttribute($key) && is_array($value) && ! array_is_list($value)) {
            $filled = array_filter($value, fn (mixed $text): bool => $text !== null && $text !== '');

            return $this->replaceTranslations($key, $filled);
        }

        return $this->setTranslatableAttribute($key, $value);
    }

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
