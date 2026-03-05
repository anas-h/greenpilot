<?php

namespace App\Jobs;

use App\Models\Alerte;
use App\Models\Bordereau;
use App\Services\TrackdechetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBsdStatutJob implements ShouldQueue
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
            Log::warning('SyncBsdStatutJob: bordereau not found', ['id' => $this->bordereauId]);

            return;
        }

        if (! $bordereau->trackdechets_id) {
            Log::warning('SyncBsdStatutJob: bordereau has no trackdechets_id', [
                'id' => $this->bordereauId,
            ]);

            return;
        }

        $service = TrackdechetsService::forGarage($bordereau->garage_id);

        if (! $service) {
            Log::info('SyncBsdStatutJob: no active Trackdechets config for garage', [
                'garage_id' => $bordereau->garage_id,
            ]);

            return;
        }

        try {
            $result = $service->getBsd($bordereau);
            $tdStatus = $this->extractStatus($result, $bordereau->type_bsd);

            if (! $tdStatus) {
                Log::warning('SyncBsdStatutJob: could not extract status from Trackdechets', [
                    'bordereau_id' => $this->bordereauId,
                    'result' => $result,
                ]);

                return;
            }

            // Map Trackdechets status to local status
            $localStatus = $this->mapTrackdechetsStatus($tdStatus);

            if ($localStatus && $localStatus !== $bordereau->statut) {
                $oldStatut = $bordereau->statut;

                // Update local status
                $updateData = ['statut' => $localStatus];

                // Set date fields based on new status
                switch ($localStatus) {
                    case 'SENT':
                        $updateData['date_enlevement'] = $updateData['date_enlevement'] ?? now();
                        break;
                    case 'RECEIVED':
                        $updateData['date_reception'] = now();
                        break;
                    case 'PROCESSED':
                        $updateData['date_traitement'] = now();
                        break;
                    case 'REFUSED':
                        $updateData['date_refus'] = now();
                        break;
                }

                $bordereau->update($updateData);

                // Record status history
                $bordereau->statutHistories()->create([
                    'ancien_statut' => $oldStatut,
                    'nouveau_statut' => $localStatus,
                    'date_changement' => now(),
                    'user_id' => null, // System sync
                    'commentaire' => 'Synchronisation automatique depuis Trackdechets',
                ]);

                Log::info('SyncBsdStatutJob: status updated', [
                    'bordereau_id' => $this->bordereauId,
                    'old_status' => $oldStatut,
                    'new_status' => $localStatus,
                    'trackdechets_status' => $tdStatus,
                ]);

                // Create critical alert if REFUSED
                if ($localStatus === 'REFUSED') {
                    $this->createRefusedAlert($bordereau);
                }
            }
        } catch (\Exception $e) {
            Log::error('SyncBsdStatutJob: failed to sync status', [
                'bordereau_id' => $this->bordereauId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract status from the GraphQL response.
     */
    private function extractStatus(array $result, string $typeBsd): ?string
    {
        $key = match ($typeBsd) {
            'BSDD' => 'bsdd',
            'BSDA' => 'bsda',
            'BSVHU' => 'bsvhu',
            default => null,
        };

        return $key ? ($result[$key]['status'] ?? null) : null;
    }

    /**
     * Map Trackdechets status to local Bordereau status.
     */
    private function mapTrackdechetsStatus(string $tdStatus): ?string
    {
        return match ($tdStatus) {
            'DRAFT' => 'DRAFT',
            'SEALED' => 'SEALED',
            'SIGNED_BY_PRODUCER' => 'SIGNED_BY_PRODUCER',
            'SENT' => 'SENT',
            'RECEIVED', 'ACCEPTED' => 'RECEIVED',
            'PROCESSED' => 'PROCESSED',
            'REFUSED' => 'REFUSED',
            'CANCELED' => 'CANCELED',
            // BSDA specific statuses
            'INITIAL' => 'DRAFT',
            'SIGNED_BY_WORKER' => 'SIGNED_BY_PRODUCER',
            // BSVHU specific
            'INITIAL' => 'DRAFT',
            default => null,
        };
    }

    /**
     * Create a critical alert for a REFUSED BSD.
     */
    private function createRefusedAlert(Bordereau $bordereau): void
    {
        // Check if alert already exists
        $exists = Alerte::where('garage_id', $bordereau->garage_id)
            ->where('type', 'bsd_refused')
            ->where('entite_type', 'bordereau')
            ->where('entite_id', $bordereau->id)
            ->where('resolue', false)
            ->exists();

        if (! $exists) {
            Alerte::create([
                'garage_id' => $bordereau->garage_id,
                'type' => 'bsd_refused',
                'priorite' => 'critique',
                'titre' => 'BSD Refuse - '.($bordereau->numero_bordereau ?? 'Brouillon #'.$bordereau->id),
                'message' => 'Le bordereau '.($bordereau->numero_bordereau ?? '#'.$bordereau->id)
                    .' pour le dechet "'.$bordereau->denomination_dechet
                    .'" a ete refuse par le destinataire.'
                    .($bordereau->motif_refus ? ' Motif: '.$bordereau->motif_refus : ''),
                'entite_type' => 'bordereau',
                'entite_id' => $bordereau->id,
                'lue' => false,
                'resolue' => false,
            ]);

            Log::info('SyncBsdStatutJob: critical alert created for REFUSED BSD', [
                'bordereau_id' => $bordereau->id,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncBsdStatutJob: job definitively failed', [
            'bordereau_id' => $this->bordereauId,
            'error' => $exception->getMessage(),
        ]);
    }
}
