<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketUpdateTest extends TestCase
{
    use RefreshDatabase;

   #[Test]
    public function it_updates_status_category_and_note_and_marks_manual(): void
    {
        $t = Ticket::factory()->create([
            'status' => 'open',
            'category' => null,
            'category_source' => 'ai',
            'note' => null,
        ]);

        $this->patchJson("/api/tickets/{$t->id}", [
            'status' => 'in_progress',
            'category' => 'technical',
            'note' => 'Investigating',
        ])->assertOk()
          ->assertJsonPath('data.status', 'in_progress')
          ->assertJsonPath('data.category', 'technical')
          ->assertJsonPath('data.note', 'Investigating');

        $this->assertDatabaseHas('tickets', [
            'id' => $t->id,
            'status' => 'in_progress',
            'category' => 'technical',
            'category_source' => 'manual', 
            'note' => 'Investigating',
        ]);
    }

   #[Test]
    public function it_validates_update_payload(): void
    {
        $t = Ticket::factory()->create();

        $this->patchJson("/api/tickets/{$t->id}", [
            'status' => 'bad-status',
        ])->assertStatus(422);
    }
}
