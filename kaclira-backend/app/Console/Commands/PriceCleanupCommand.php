<?php

namespace App\Console\Commands;

use App\Models\PriceHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PriceCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'price:cleanup 
                            {--days=365 : Keep price history for this many days}
                            {--dry-run : Run without actually deleting records}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old price history records to maintain database performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Starting price history cleanup...");
        $this->info("Keeping records from last {$days} days");
        $this->info("Dry run: " . ($dryRun ? 'Yes' : 'No'));

        try {
            $cutoffDate = Carbon::now()->subDays($days);
            
            // Count records to be deleted
            $recordsToDelete = PriceHistory::where('created_at', '<', $cutoffDate)->count();
            
            if ($recordsToDelete === 0) {
                $this->info("No old records found to cleanup");
                return Command::SUCCESS;
            }

            $this->info("Found {$recordsToDelete} records older than {$cutoffDate->format('Y-m-d')}");

            if (!$dryRun) {
                if ($this->confirm("Do you want to delete {$recordsToDelete} price history records?")) {
                    // Delete in chunks to avoid memory issues
                    $deleted = 0;
                    $chunkSize = 1000;
                    
                    $this->output->progressStart($recordsToDelete);
                    
                    while (true) {
                        $chunk = PriceHistory::where('created_at', '<', $cutoffDate)
                            ->limit($chunkSize)
                            ->get();
                            
                        if ($chunk->isEmpty()) {
                            break;
                        }
                        
                        foreach ($chunk as $record) {
                            $record->delete();
                            $deleted++;
                            $this->output->progressAdvance();
                        }
                        
                        // Small delay to prevent overwhelming the database
                        usleep(100000); // 0.1 seconds
                    }
                    
                    $this->output->progressFinish();
                    $this->info("Successfully deleted {$deleted} price history records");
                    
                    Log::info('Price history cleanup completed', [
                        'cutoff_date' => $cutoffDate->format('Y-m-d'),
                        'records_deleted' => $deleted
                    ]);
                } else {
                    $this->info("Cleanup cancelled by user");
                    return Command::SUCCESS;
                }
            } else {
                $this->info("Would delete {$recordsToDelete} records (dry run)");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Price cleanup failed: " . $e->getMessage());
            Log::error('Price cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
