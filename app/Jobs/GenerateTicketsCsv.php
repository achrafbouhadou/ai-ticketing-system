<?php

namespace App\Jobs;

use App\Models\DataExport;
use App\Repositories\TicketRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateTicketsCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $exportId) {}

    public function handle(TicketRepository $tickets): void
    {
        $export = DataExport::find($this->exportId);
        if (!$export || $export->status === 'done') return;

        $export->update(['status' => 'running', 'error' => null]);

        $filters = $export->params ?? [];
        $relPath = 'exports/tickets_'.$export->id.'.csv';
        $absPath = Storage::disk('local')->path($relPath);

        try {
            Storage::disk('local')->makeDirectory('exports');

            $out = fopen($absPath, 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['id','subject','status','category','confidence','classified_at','note','created_at']);

            $tickets->filteredQuery($filters)
                ->orderBy('id') 
                ->chunkById(1000, function ($chunk) use ($out) {
                    foreach ($chunk as $t) {
                        fputcsv($out, [
                            (string) $t->id,
                            $t->subject,
                            $t->status,
                            $t->category,
                            is_null($t->confidence) ? null : (float) $t->confidence,
                            optional($t->classified_at)?->toDateTimeString(),
                            $t->note,
                            optional($t->created_at)?->toDateTimeString(),
                        ]);
                    }
                }, 'id');

            fclose($out);

            $export->update([
                'status'     => 'done',
                'file_path'  => $relPath,
                'expires_at' => now()->addDay(),
            ]);
        } catch (\Throwable $e) {
            @fclose($out);
            $export->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
