<?php

namespace Tests\Feature;

use App\Jobs\GenerateTicketsCsv;
use App\Models\DataExport;
use App\Models\Ticket;
use App\Repositories\TicketRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportQueuedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_export_job_generates_file_and_serves_download(): void
    {
        Storage::fake('local');

        Ticket::factory()->count(7)->create(['status' => 'open']);
        Ticket::factory()->count(3)->create(['status' => 'resolved']);

        $res = $this->postJson('/api/tickets/export', ['status' => 'open'])
            ->assertStatus(202);

        $exportId = $res->json('export_id');

        // Run the job synchronously to simulate the worker
        $job = app()->makeWith(GenerateTicketsCsv::class, ['exportId' => $exportId]);
        $job->handle(app(TicketRepository::class));

        $export = DataExport::findOrFail($exportId);
        $this->assertEquals('done', $export->status);
        $this->assertNotNull($export->file_path);
        Storage::disk('local')->assertExists($export->file_path);

        $this->getJson("/api/tickets/export/{$exportId}")
            ->assertOk()
            ->assertJsonPath('status', 'done');

        $download = $this->get("/api/tickets/export/{$exportId}/download")
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('attachment;', $download->headers->get('content-disposition'));
    }
}
