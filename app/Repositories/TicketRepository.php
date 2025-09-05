<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;


interface TicketRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function create(array $data): Ticket;

    public function findOrFail(string $id): Ticket;

    public function filteredQuery(array $filters): Builder;

    public function update(string $id, array $data): Ticket;
}
