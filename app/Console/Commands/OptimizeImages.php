<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImageOptimizer;
use File;

class OptimizeImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan images:optimize storage/app/public/uploads
     */
    protected $signature = 'images:optimize {path=storage/app/public/uploads/products_test}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize all images in a directory using TinyJPG API';

    /**
     * Execute the console command.
     */
    public function handle(ImageOptimizer $optimizer)
    {
        $path = base_path($this->argument('path'));

        if (!is_dir($path)) {
            $this->error("Directory does not exist: {$path}");
            return Command::FAILURE;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            if (in_array(strtolower($file->getExtension()), ['jpg','jpeg','png'])) {
                $this->info("Optimizing: " . $file->getRelativePathname());
                $optimizer->optimize($file->getPathname());
            }
        }

        $this->info('✅ Image optimization completed!');
        return Command::SUCCESS;
    }
}
