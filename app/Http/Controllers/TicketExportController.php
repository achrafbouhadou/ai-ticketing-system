<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateTicketsCsv;
use App\Models\DataExport;
use App\Repositories\TicketRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketExportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $filters = $request->only(['q','status','category','has_note']);

        $export = DataExport::create([
            'type'   => 'tickets_csv',
            'status' => 'pending',
            'params' => $filters,
        ]);

        GenerateTicketsCsv::dispatch($export->id);

        return response()->json([
            'export_id' => (string) $export->id,
            'status'    => $export->status,
        ], 202);
    }

    public function show(string $id): JsonResponse
    {
        $export = DataExport::findOrFail($id);

        $downloadUrl = null;
        if ($export->status === 'done' && $export->file_path && Storage::disk('local')->exists($export->file_path)) {
            $downloadUrl = route('tickets.export.download', ['id' => $export->id]);
        }

        return response()->json([
            'id'         => (string) $export->id,
            'type'       => $export->type,
            'status'     => $export->status,
            'error'      => $export->error,
            'expires_at' => optional($export->expires_at)?->toIso8601String(),
            'download_url' => $downloadUrl,
        ]);
    }

    public function download(string $id): StreamedResponse
    {
        $export = DataExport::findOrFail($id);

        abort_unless($export->status === 'done' && $export->file_path, 404, 'Not ready.');
        if ($export->expires_at && now()->greaterThan($export->expires_at)) {
            abort(410, 'Export expired.');
        }
        abort_unless(Storage::disk('local')->exists($export->file_path), 404, 'File missing.');

        $filename = 'tickets_'.$export->id.'.csv';

        return Storage::disk('local')->download(
            $export->file_path,
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }
}
