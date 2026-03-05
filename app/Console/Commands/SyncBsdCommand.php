<?php

namespace App\Console\Commands;

use App\Jobs\SyncBsdStatutJob;
use App\Models\Bordereau;
use Illuminate\Console\Command;

class SyncBsdCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dechets:sync-bsd
                            {--garage= : Sync only a specific garage ID}
                            {--bordereau= : Sync only a specific bordereau ID}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronise les statuts des BSD actifs avec Trackdechets (SENT, RECEIVED statuses)';

    public function handle(): int
    {
        $this->info('Demarrage de la synchronisation des BSD avec Trackdechets...');

        $query = Bordereau::whereNotNull('trackdechets_id')
            ->whereIn('statut', ['SENT', 'RECEIVED', 'SIGNED_BY_PRODUCER']);

        // Filter by specific garage
        if ($garageId = $this->option('garage')) {
            $query->where('garage_id', $garageId);
            $this->info("Filtre sur le garage ID: {$garageId}");
        }

        // Filter by specific bordereau
        if ($bordereauId = $this->option('bordereau')) {
            $query->where('id', $bordereauId);
            $this->info("Filtre sur le bordereau ID: {$bordereauId}");
        }

        $bordereaux = $query->get();

        if ($bordereaux->isEmpty()) {
            $this->info('Aucun BSD actif a synchroniser.');

            return self::SUCCESS;
        }

        $this->info("Nombre de BSD a synchroniser: {$bordereaux->count()}");

        $dispatched = 0;
        foreach ($bordereaux as $bordereau) {
            SyncBsdStatutJob::dispatch($bordereau->id);
            $dispatched++;

            $this->line(sprintf(
                '  -> BSD #%d (%s) - %s [%s]',
                $bordereau->id,
                $bordereau->trackdechets_id,
                $bordereau->type_bsd,
                $bordereau->statut
            ));
        }

        $this->info("{$dispatched} job(s) de synchronisation dispatche(s).");

        return self::SUCCESS;
    }
}
