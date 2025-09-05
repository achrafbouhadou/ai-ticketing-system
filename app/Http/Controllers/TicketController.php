<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private TicketService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only(['q','status','category']);
        $perPage = (int) $request->input('per_page', 10);

        $tickets = $this->service->list($filters, $perPage);

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request)
    {
        $ticket = $this->service->create($request->validated());

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $ticket = $this->service->get($id);
        return new TicketResource($ticket);
    }
}
