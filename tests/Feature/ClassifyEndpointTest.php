<?php

namespace Tests\Feature;

use App\Jobs\ClassifyTicket;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ClassifyEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_queues_classification_job_and_responds_202(): void
    {
        Queue::fake();

        $t = Ticket::factory()->create();

        $this->postJson("/api/tickets/{$t->id}/classify")
            ->assertStatus(202)
            ->assertJsonPath('status', 'accepted');

        Queue::assertPushed(ClassifyTicket::class, function ($job) use ($t) {
            return $job->ticketId === (string) $t->id;
        });
    }
}
