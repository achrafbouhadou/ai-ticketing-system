<?php

namespace App\Http\Controllers;

use App\Jobs\ClassifyTicket;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class ClassificationController extends Controller
{
    public function store(string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        ClassifyTicket::dispatch($ticket->id);

        return response()->json([
            'status'  => 'accepted',
            'message' => 'Classification queued.',
            'ticket_id' => (string)$ticket->id,
        ], 202);
    }
}
