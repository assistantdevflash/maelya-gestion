<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimise l\'application pour les performances maximales';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Optimisation des performances...');
        $this->newLine();

        // 1. Cache des vues
        $this->task('Compilation des vues', function () {
            $this->call('view:cache');
            return true;
        });

        // 2. Cache de configuration
        $this->task('Cache de configuration', function () {
            $this->call('config:cache');
            return true;
        });

        // 3. Cache des routes
        $this->task('Cache des routes', function () {
            $this->call('route:cache');
            return true;
        });

        // 4. Cache des événements
        $this->task('Cache des événements', function () {
            $this->call('event:cache');
            return true;
        });

        // 5. Optimisation de l'autoloader
        $this->task('Optimisation Composer', function () {
            exec('composer dump-autoload --optimize --no-dev', $output, $returnCode);
            return $returnCode === 0;
        });

        $this->newLine();
        $this->info('✅ Optimisation terminée !');
        $this->newLine();
        
        $this->components->bulletList([
            'Vues compilées',
            'Config en cache',
            'Routes en cache',
            'Événements en cache',
            'Autoloader optimisé',
        ]);

        $this->newLine();
        $this->comment('💡 Pour désactiver le cache en dev : php artisan optimize:clear');

        return Command::SUCCESS;
    }
}
