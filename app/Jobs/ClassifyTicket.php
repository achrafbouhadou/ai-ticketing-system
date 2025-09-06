<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Exceptions\RateLimitException;
use Throwable;

class ClassifyTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum attempts before failing the job.
     */
    public int $tries = 8;

    public function __construct(public string $ticketId) {}

    public function handle(TicketClassifier $classifier): void
    {
        $ticket = Ticket::find($this->ticketId);
        if (!$ticket) return;

        try {
            $result = $classifier->classify($ticket);
        } catch (RateLimitException $e) {
            $attempt = max(1, $this->attempts());
            $delay = min(300, (int) (pow(2, $attempt) * 2)); // 2,4,8,... capped at 300s
            report($e);
            $this->release($delay);
            return;
        } catch (Throwable $e) {
            // Let other exceptions bubble after logging; they will count toward $tries
            report($e);
            throw $e;
        }

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
