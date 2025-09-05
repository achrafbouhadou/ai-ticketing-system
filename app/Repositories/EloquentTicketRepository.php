<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentTicketRepository implements TicketRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $q        = $filters['q']        ?? null;
        $status   = $filters['status']   ?? null;
        $category = $filters['category'] ?? null;

        return Ticket::query()
            ->when($q, function ($qry) use ($q) {
                $qry->where(function ($w) use ($q) {
                    $w->where('subject', 'like', "%{$q}%")
                      ->orWhere('body', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($qry) => $qry->where('status', $status))
            ->when($category, fn ($qry) => $qry->where('category', $category))
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }

    public function findOrFail(string $id): Ticket
    {
        return Ticket::findOrFail($id);
    }
}
