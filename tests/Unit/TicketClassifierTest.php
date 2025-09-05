<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketClassifierTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function fallback_classifier_assigns_expected_categories(): void
    {
        $svc = app(TicketClassifier::class);

        $billing = Ticket::factory()->create(['subject' => 'Refund my invoice']);
        $r1 = $svc->classify($billing);
        $this->assertEquals('billing', $r1->category);

        $tech = Ticket::factory()->create(['subject' => 'API error on payment']);
        $r2 = $svc->classify($tech);
        $this->assertEquals('technical', $r2->category);

        $acct = Ticket::factory()->create(['subject' => 'Forgot my password, account locked']);
        $r3 = $svc->classify($acct);
        $this->assertEquals('account', $r3->category);
    }
}
