<?php

namespace App\Console\Commands;

use App\Models\ClientPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UpdateExistingClientPhotosMimeTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:update-mime-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour les mime_type et extension des photos clients existantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mise à jour des mime_types des photos clients...');
        
        $photos = ClientPhoto::whereNull('mime_type')->get();
        
        if ($photos->isEmpty()) {
            $this->info('Aucune photo à mettre à jour.');
            return 0;
        }
        
        $bar = $this->output->createProgressBar($photos->count());
        $bar->start();
        
        $updated = 0;
        foreach ($photos as $photo) {
            // Extraire l'extension du path
            $extension = pathinfo($photo->path, PATHINFO_EXTENSION);
            
            // Déterminer le mime_type basé sur l'extension
            $mimeType = match(strtolower($extension)) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
                default => 'application/octet-stream',
            };
            
            $photo->update([
                'mime_type' => $mimeType,
                'extension' => strtolower($extension),
            ]);
            
            $updated++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("✓ {$updated} photo(s) mise(s) à jour.");
        
        return 0;
    }
}
