<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasStableKey;
use App\Rules\AudioPath;
use Database\Factories\PassageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reading text or listening material shared by several exercises or exam questions.
 *
 * @property int $id
 * @property string $key
 * @property string $title
 * @property string|null $body Markdown (reading passages).
 * @property string|null $transcript Listening script; hidden from serialisation.
 * @property string|null $audio_path Path on the private `content-audio` disk.
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'title', 'body', 'transcript', 'audio_path'])]
#[Hidden(['transcript'])]
class Passage extends Model
{
    /** @use HasFactory<PassageFactory> */
    use Archivable, HasFactory, HasStableKey;

    /** @return HasMany<Exercise, $this> */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $passage): void {
            AudioPath::assert('audio_path', $passage->audio_path);
        });
    }
}
