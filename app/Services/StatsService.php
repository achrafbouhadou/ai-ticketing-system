<?php

namespace App\Services;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class StatsService
{
    public function get(): array
    {
        $statusKeys = collect(TicketStatus::cases())->map->value->all();
        $statusCounts = array_fill_keys($statusKeys, 0);

        Ticket::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->get()
            ->each(function ($row) use (&$statusCounts) {
                $key = $row->status?->value;
                if ($key) $statusCounts[$key] = (int) $row->c;
            });

        $categoryKeys = collect(TicketCategory::cases())->map->value->all();
        $categoryCounts = array_fill_keys($categoryKeys, 0);
        $unclassified = 0;

        Ticket::query()
            ->selectRaw('category, COUNT(*) as c')
            ->groupBy('category')
            ->get()
            ->each(function ($row) use (&$categoryCounts, &$unclassified) {
                $key = $row->category?->value;
                if ($key === null) {
                    $unclassified = (int) $row->c;
                } else {
                    $categoryCounts[$key] = (int) $row->c;
                }
            });

        return [
            'status_counts'   => $statusCounts,
            'category_counts' => $categoryCounts + ['unclassified' => $unclassified],
        ];
    }
}
