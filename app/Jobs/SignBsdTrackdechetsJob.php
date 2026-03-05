<?php

namespace App\Jobs;

use App\Models\Bordereau;
use App\Services\TrackdechetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SignBsdTrackdechetsJob implements ShouldQueue
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
        private int $bordereauId,
        private string $signatureType = 'EMISSION'
    ) {}

    public function handle(): void
    {
        $bordereau = Bordereau::with(['garage.entreprise'])->find($this->bordereauId);

        if (! $bordereau) {
            Log::warning('SignBsdTrackdechetsJob: bordereau not found', ['id' => $this->bordereauId]);

            return;
        }

        if (! $bordereau->trackdechets_id) {
            Log::warning('SignBsdTrackdechetsJob: bordereau has no trackdechets_id', [
                'id' => $this->bordereauId,
            ]);

            return;
        }

        $service = TrackdechetsService::forGarage($bordereau->garage_id);

        if (! $service) {
            Log::info('SignBsdTrackdechetsJob: no active Trackdechets config for garage', [
                'garage_id' => $bordereau->garage_id,
            ]);

            return;
        }

        try {
            $result = $service->signBsd($bordereau, $this->signatureType);

            // Extract the new status from Trackdechets response
            $newStatus = $this->extractStatus($result, $bordereau->type_bsd);

            if ($newStatus) {
                Log::info('SignBsdTrackdechetsJob: BSD signed on Trackdechets', [
                    'bordereau_id' => $this->bordereauId,
                    'trackdechets_id' => $bordereau->trackdechets_id,
                    'signature_type' => $this->signatureType,
                    'new_trackdechets_status' => $newStatus,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SignBsdTrackdechetsJob: failed to sign BSD on Trackdechets', [
                'bordereau_id' => $this->bordereauId,
                'signature_type' => $this->signatureType,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Extract the status from the GraphQL response.
     */
    private function extractStatus(array $result, string $typeBsd): ?string
    {
        $key = match ($typeBsd) {
            'BSDD' => 'signEmissionForm',
            'BSDA' => 'signBsda',
            'BSVHU' => 'signBsvhu',
            default => null,
        };

        return $key ? ($result[$key]['status'] ?? null) : null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SignBsdTrackdechetsJob: job definitively failed', [
            'bordereau_id' => $this->bordereauId,
            'signature_type' => $this->signatureType,
            'error' => $exception->getMessage(),
        ]);
    }
}
