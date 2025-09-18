<?php

namespace App\Console\Commands;

use App\Services\CategoryService;
use Illuminate\Console\Command;

class SyncGoogleCategories extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'categories:sync-google 
                            {--force : Force sync even if recently synced}
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Sync categories from Google Merchant Center taxonomy';

    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        parent::__construct();
        $this->categoryService = $categoryService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Google Categories sync...');

        try {
            if ($this->option('dry-run')) {
                $this->warn('DRY RUN MODE - No changes will be made');
                // In a real implementation, you'd show what would be synced
                $this->info('Would sync Google categories (dry run)');
                return self::SUCCESS;
            }

            $stats = $this->categoryService->syncGoogleCategories();

            $this->info('Sync completed successfully!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Categories', $stats['total']],
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                    ['Errors', $stats['errors']],
                ]
            );

            if ($stats['errors'] > 0) {
                $this->warn("There were {$stats['errors']} errors during sync. Check logs for details.");
                return self::FAILURE;
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
