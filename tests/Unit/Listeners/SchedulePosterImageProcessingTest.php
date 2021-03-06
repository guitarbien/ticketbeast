<?php

namespace Tests\Unit\Listeners;

use App\Concert;
use App\Events\ConcertAdded;
use App\Jobs\ProcessPosterImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Class SchedulePosterImageProcessingTest
 * @package Tests\Unit\Listeners
 */
class SchedulePosterImageProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_若poster_image存在則會產生一個queue_job來處理image()
    {
        Queue::fake();

        $concert = Concert::factory()->unpublished()->create([
            'poster_image_path' => 'posters/example-poster.png',
        ]);

        // It will push a job (ProcessPosterImage) to a queue when dispatch an event
        ConcertAdded::dispatch($concert);

        Queue::assertPushed(ProcessPosterImage::class, function($job) use($concert) {
            return $job->concert->is($concert);
        });
    }

    public function test_若poster_image不存在則不會產生queue_job()
    {
        Queue::fake();

        $concert = Concert::factory()->unpublished()->create([
            'poster_image_path' => null,
        ]);

        // It will push a job (ProcessPosterImage) to a queue when dispatch an event
        ConcertAdded::dispatch($concert);

        Queue::assertNotPushed(ProcessPosterImage::class);
    }
}
