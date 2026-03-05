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

class CreateBsdTrackdechetsJob implements ShouldQueue
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
        $bordereau = Bordereau::with(['garage.entreprise'])->find($this->bordereauId);

        if (! $bordereau) {
            Log::warning('CreateBsdTrackdechetsJob: bordereau not found', ['id' => $this->bordereauId]);

            return;
        }

        // Skip if already has a trackdechets_id
        if ($bordereau->trackdechets_id) {
            Log::info('CreateBsdTrackdechetsJob: bordereau already has trackdechets_id', [
                'id' => $this->bordereauId,
                'trackdechets_id' => $bordereau->trackdechets_id,
            ]);

            return;
        }

        $service = TrackdechetsService::forGarage($bordereau->garage_id);

        if (! $service) {
            Log::info('CreateBsdTrackdechetsJob: no active Trackdechets config for garage', [
                'garage_id' => $bordereau->garage_id,
            ]);

            return;
        }

        try {
            $result = $service->createBsd($bordereau);

            // Extract the ID from the appropriate response key
            $tdId = $this->extractTrackdechetsId($result, $bordereau->type_bsd);

            if ($tdId) {
                $bordereau->update([
                    'trackdechets_id' => $tdId,
                    'statut_sync' => 'synchronise',
                    'erreur_sync' => null,
                    'derniere_sync' => now(),
                ]);

                Log::info('CreateBsdTrackdechetsJob: BSD created on Trackdechets', [
                    'bordereau_id' => $this->bordereauId,
                    'trackdechets_id' => $tdId,
                    'type_bsd' => $bordereau->type_bsd,
                ]);

                // Also publish on Trackdechets if local status is already SEALED
                if ($bordereau->statut === 'SEALED') {
                    $service->publishBsd($bordereau);
                    Log::info('CreateBsdTrackdechetsJob: BSD published on Trackdechets', [
                        'bordereau_id' => $this->bordereauId,
                    ]);
                }
            }
        } catch (\Exception $e) {
            $bordereau->update([
                'statut_sync' => 'erreur',
                'erreur_sync' => $e->getMessage(),
                'derniere_sync' => now(),
            ]);

            Log::error('CreateBsdTrackdechetsJob: failed to create BSD on Trackdechets', [
                'bordereau_id' => $this->bordereauId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Extract the Trackdechets ID from the GraphQL response.
     */
    private function extractTrackdechetsId(array $result, string $typeBsd): ?string
    {
        $key = match ($typeBsd) {
            'BSDD' => 'createForm',
            'BSDA' => 'createDraftBsda',
            'BSVHU' => 'createDraftBsvhu',
            default => null,
        };

        return $key ? ($result[$key]['id'] ?? null) : null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $bordereau = Bordereau::find($this->bordereauId);
        if ($bordereau) {
            $bordereau->update([
                'statut_sync' => 'erreur',
                'erreur_sync' => $exception->getMessage(),
                'derniere_sync' => now(),
            ]);
        }

        Log::error('CreateBsdTrackdechetsJob: job definitively failed', [
            'bordereau_id' => $this->bordereauId,
            'error' => $exception->getMessage(),
        ]);
    }
}
