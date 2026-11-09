<?php

namespace Tests\Fixtures\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * A job that does nothing, for tests that only check where jobs are pushed.
 */
class NoopJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void {}
}
