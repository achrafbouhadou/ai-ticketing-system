<?php

namespace App\Services;

use App\Models\Ticket;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketService
{
    public function __construct(private TicketRepository $tickets) {}

    public function list(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->tickets->paginate($filters, $perPage);
    }

    public function create(array $data): Ticket
    {
        return $this->tickets->create($data);
    }

    public function get(string $id): Ticket
    {
        return $this->tickets->findOrFail($id);
    }
}
