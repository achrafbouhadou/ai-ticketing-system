<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClassifyTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $ticketId) {}

    public function handle(TicketClassifier $classifier): void
    {
        $ticket = Ticket::find($this->ticketId);
        if (!$ticket) return;

        $result = $classifier->classify($ticket);

        $canSetCategory = ($ticket->category_source !== 'manual') || is_null($ticket->category);

        if ($canSetCategory && $result->category) {
            $ticket->category = $result->category;
            $ticket->category_source = 'ai';
        }

        $ticket->explanation   = $result->explanation;
        $ticket->confidence    = $result->confidence;
        $ticket->classified_at = now();

        $ticket->save();
    }
}
