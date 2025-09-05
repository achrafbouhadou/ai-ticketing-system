<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_status_and_category_counts(): void
    {
        Ticket::factory()->count(2)->create(['status' => 'open', 'category' => null]);
        Ticket::factory()->count(3)->create(['status' => 'in_progress', 'category' => 'technical']);
        Ticket::factory()->count(1)->create(['status' => 'resolved', 'category' => 'billing']);

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonStructure([
                'status_counts' => ['open','in_progress','resolved'],
                'category_counts' => ['billing','technical','account','other','unclassified'],
            ])
            ->assertJsonPath('status_counts.open', 2)
            ->assertJsonPath('status_counts.in_progress', 3)
            ->assertJsonPath('status_counts.resolved', 1)
            ->assertJsonPath('category_counts.technical', 3)
            ->assertJsonPath('category_counts.billing', 1)
            ->assertJsonPath('category_counts.unclassified', 2);
    }
}
