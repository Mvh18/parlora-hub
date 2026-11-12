<?php

namespace Tests\Fixtures\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasStableKey;
use Illuminate\Database\Eloquent\Model;

/**
 * Test-only model for the shared content concerns, backed by a table the
 * tests create themselves (no content table exists in the application yet).
 */
class ContentStub extends Model
{
    use Archivable, HasStableKey;

    protected $table = 'content_stubs';

    protected $guarded = [];
}
