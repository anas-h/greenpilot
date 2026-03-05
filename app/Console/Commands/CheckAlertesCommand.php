<?php

namespace App\Console\Commands;

use App\Jobs\CheckAlertesJob;
use App\Models\Garage;
use Illuminate\Console\Command;

class CheckAlertesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dechets:check-alertes
                            {--garage= : Check only a specific garage ID}
                            {--sync : Run synchronously instead of dispatching jobs}';

    /**
     * The console command description.
     */
    protected $description = 'Verifie les alertes pour tous les garages actifs (conteneurs, stockage, autorisations, BSD, FDS, etc.)';

    public function handle(): int
    {
        $this->info('Demarrage de la verification des alertes...');

        if ($garageId = $this->option('garage')) {
            // Check alerts for a specific garage
            $garage = Garage::find($garageId);
            if (! $garage) {
                $this->error("Garage ID {$garageId} non trouve.");

                return self::FAILURE;
            }

            $this->info("Verification des alertes pour le garage: {$garage->nom} (ID: {$garageId})");

            if ($this->option('sync')) {
                $alerteService = app(\App\Services\AlerteService::class);
                $created = $alerteService->checkAllForGarage($garageId);
                $this->info(count($created).' alerte(s) creee(s).');
                foreach ($created as $alerte) {
                    $this->line(sprintf(
                        '  [%s] %s - %s',
                        strtoupper($alerte->priorite),
                        $alerte->type,
                        $alerte->titre
                    ));
                }
            } else {
                CheckAlertesJob::dispatch($garageId);
                $this->info('Job de verification dispatche.');
            }

            return self::SUCCESS;
        }

        // Check all active garages
        $garages = Garage::where('actif', true)->get();

        if ($garages->isEmpty()) {
            $this->info('Aucun garage actif trouve.');

            return self::SUCCESS;
        }

        $this->info("Nombre de garages actifs: {$garages->count()}");

        if ($this->option('sync')) {
            $alerteService = app(\App\Services\AlerteService::class);
            $totalCreated = 0;

            foreach ($garages as $garage) {
                $this->line("  -> Garage: {$garage->nom} (ID: {$garage->id})");
                try {
                    $created = $alerteService->checkAllForGarage($garage->id);
                    $count = count($created);
                    $totalCreated += $count;
                    if ($count > 0) {
                        $this->line("     {$count} alerte(s) creee(s)");
                    }
                } catch (\Exception $e) {
                    $this->error("     Erreur: {$e->getMessage()}");
                }
            }

            $this->info("Total: {$totalCreated} alerte(s) creee(s).");
        } else {
            // Dispatch one job per garage for parallelism
            foreach ($garages as $garage) {
                CheckAlertesJob::dispatch($garage->id);
                $this->line("  -> Job dispatche pour le garage: {$garage->nom} (ID: {$garage->id})");
            }
            $this->info($garages->count().' job(s) de verification dispatche(s).');
        }

        return self::SUCCESS;
    }
}
