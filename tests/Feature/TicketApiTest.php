<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_ticket(): void
    {
        $payload = ['subject' => 'Payment failed', 'body' => 'Card was declined during checkout.'];

        $res = $this->postJson('/api/tickets', $payload)
            ->assertCreated()
            ->assertJsonPath('data.subject', 'Payment failed');

        $this->assertDatabaseHas('tickets', ['subject' => 'Payment failed']);
    }

    #[Test]
    public function it_validates_create_payload(): void
    {
        $this->postJson('/api/tickets', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject','body']);
    }

   #[Test]
    public function it_lists_tickets_with_filters_and_clamps_per_page(): void
    {
        Ticket::factory()->count(8)->create(['status' => 'open']);
        Ticket::factory()->count(3)->create(['status' => 'resolved', 'subject' => 'Invoice needed']);

        $res = $this->getJson('/api/tickets?status=resolved&q=Invoice&per_page=1000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)   
            ->assertJsonPath('meta.current_page', 1);

        $this->assertGreaterThanOrEqual(1, count($res->json('data', [])));
        $this->assertSame(3, $res->json('meta.total'));
    }
}
