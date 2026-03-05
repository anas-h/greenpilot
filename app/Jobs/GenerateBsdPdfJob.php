<?php

namespace App\Jobs;

use App\Models\Bordereau;
use App\Services\TrackdechetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateBsdPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retries.
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private int $bordereauId
    ) {}

    public function handle(): void
    {
        $bordereau = Bordereau::with(['garage'])->find($this->bordereauId);

        if (! $bordereau) {
            Log::warning('GenerateBsdPdfJob: bordereau not found', ['id' => $this->bordereauId]);

            return;
        }

        if (! $bordereau->trackdechets_id) {
            Log::warning('GenerateBsdPdfJob: bordereau has no trackdechets_id', [
                'id' => $this->bordereauId,
            ]);

            return;
        }

        $service = TrackdechetsService::forGarage($bordereau->garage_id);

        if (! $service) {
            Log::info('GenerateBsdPdfJob: no active Trackdechets config for garage', [
                'garage_id' => $bordereau->garage_id,
            ]);

            return;
        }

        try {
            // Get the PDF download link from Trackdechets
            $downloadLink = $service->getBsdPdf($bordereau);

            if (! $downloadLink) {
                Log::warning('GenerateBsdPdfJob: no download link returned', [
                    'bordereau_id' => $this->bordereauId,
                    'trackdechets_id' => $bordereau->trackdechets_id,
                ]);

                return;
            }

            // Download the PDF
            $response = Http::get($downloadLink);

            if ($response->failed()) {
                Log::error('GenerateBsdPdfJob: failed to download PDF', [
                    'bordereau_id' => $this->bordereauId,
                    'status' => $response->status(),
                ]);
                throw new \RuntimeException('Failed to download PDF: HTTP '.$response->status());
            }

            // Store the PDF locally
            $garageId = $bordereau->garage_id;
            $filename = sprintf(
                'bsd/%d/%s_%s.pdf',
                $garageId,
                $bordereau->type_bsd,
                $bordereau->trackdechets_id
            );

            Storage::disk('local')->put($filename, $response->body());

            // Update the bordereau with the PDF path
            $bordereau->update([
                'pdf_path' => $filename,
            ]);

            Log::info('GenerateBsdPdfJob: PDF stored successfully', [
                'bordereau_id' => $this->bordereauId,
                'pdf_path' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error('GenerateBsdPdfJob: failed to generate/store PDF', [
                'bordereau_id' => $this->bordereauId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateBsdPdfJob: job definitively failed', [
            'bordereau_id' => $this->bordereauId,
            'error' => $exception->getMessage(),
        ]);
    }
}
