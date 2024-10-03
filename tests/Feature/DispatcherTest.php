<?php

namespace Tests\Feature;

use App\Jobs\SyncPayItems;
use App\Models\Business;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that only enabled jobs are queued
     */
    public function test_jobs_queued(): void
    {
        $this->seed();
        Queue::fake();
        $response = $this->get('/dispatch');

        $response->assertStatus(200);
        Queue::assertPushed(SyncPayItems::class, function($job) {
            return $job->business instanceof Business
                && $job->business->enabled;
        });
        Queue::assertCount(7);
    }
}
