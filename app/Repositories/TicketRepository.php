<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function create(array $data): Ticket;

    public function findOrFail(string $id): Ticket;
}
