<?php

namespace App\Http\Controllers;

use App\Repositories\TicketRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;

class TicketExportController extends Controller
{
    public function __construct(private TicketRepository $tickets) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->only(['q','status','category','has_note']);

        $filename = 'tickets_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');

            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, [
                'id','subject','status','category','confidence','classified_at','note','created_at'
            ]);

            // Stream rows
            $this->tickets->filteredQuery($filters)
                ->orderBy('created_at', 'desc')
                ->select(['id','subject','status','category','confidence','classified_at','note','created_at'])
                ->chunkById(500, function ($chunk) use ($out) {
                    foreach ($chunk as $t) {
                        fputcsv($out, [
                            (string) $t->id,
                            $t->subject,
                            $t->status,
                            $t->category,
                            is_null($t->confidence) ? null : (float) $t->confidence,
                            optional($t->classified_at)->toDateTimeString(),
                            $t->note,
                            optional($t->created_at)->toDateTimeString(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, $headers);
    }
}
