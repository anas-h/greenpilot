<?php

namespace App\Jobs;

use App\Models\Garage;
use App\Services\AlerteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAlertesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Timeout in seconds.
     */
    public int $timeout = 300;

    public function __construct(
        private ?int $garageId = null
    ) {}

    public function handle(AlerteService $alerteService): void
    {
        if ($this->garageId) {
            // Check alerts for a specific garage
            $this->checkForGarage($alerteService, $this->garageId);

            return;
        }

        // Check alerts for all active garages
        $garages = Garage::where('actif', true)->pluck('id');

        Log::info('CheckAlertesJob: checking alerts for '.$garages->count().' active garages');

        foreach ($garages as $garageId) {
            try {
                $this->checkForGarage($alerteService, $garageId);
            } catch (\Exception $e) {
                Log::error('CheckAlertesJob: error checking alerts for garage', [
                    'garage_id' => $garageId,
                    'error' => $e->getMessage(),
                ]);
                // Continue with next garage
            }
        }
    }

    /**
     * Run all alert checks for a given garage.
     */
    private function checkForGarage(AlerteService $alerteService, int $garageId): void
    {
        $created = $alerteService->checkAllForGarage($garageId);

        if (! empty($created)) {
            Log::info('CheckAlertesJob: alerts created', [
                'garage_id' => $garageId,
                'count' => count($created),
                'types' => array_unique(array_column(
                    array_map(fn ($a) => $a->toArray(), $created),
                    'type'
                )),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckAlertesJob: job failed', [
            'garage_id' => $this->garageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
