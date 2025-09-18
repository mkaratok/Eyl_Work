<?php

namespace App\Console\Commands;

use App\Models\ProductPrice;
use App\Models\PriceHistory;
use App\Jobs\SendPriceAlertJob;
use App\Services\PriceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PriceMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'price:monitor 
                            {--hours=1 : Hours to look back for price changes}
                            {--threshold=5.0 : Minimum percentage change to trigger alerts}
                            {--dry-run : Run without sending actual alerts}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor price changes and send alerts for significant drops';

    protected PriceService $priceService;

    public function __construct(PriceService $priceService)
    {
        parent::__construct();
        $this->priceService = $priceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = $this->option('hours');
        $threshold = $this->option('threshold');
        $dryRun = $this->option('dry-run');

        $this->info("Starting price monitoring...");
        $this->info("Looking back: {$hours} hours");
        $this->info("Threshold: {$threshold}%");
        $this->info("Dry run: " . ($dryRun ? 'Yes' : 'No'));

        try {
            $startTime = Carbon::now()->subHours($hours);
            
            // Get recent price changes
            $priceChanges = PriceHistory::where('created_at', '>=', $startTime)
                ->whereIn('change_type', ['price_decrease', 'both'])
                ->with(['productPrice.product', 'productPrice.seller'])
                ->get();

            $this->info("Found {$priceChanges->count()} price changes to analyze");

            $alertsSent = 0;
            $significantDrops = 0;

            foreach ($priceChanges as $change) {
                $decreasePercentage = (($change->old_price - $change->new_price) / $change->old_price) * 100;
                
                if ($decreasePercentage >= $threshold) {
                    $significantDrops++;
                    
                    $this->line("Significant drop detected:");
                    $this->line("  Product: " . ($change->productPrice->product->name ?? 'Unknown'));
                    $this->line("  Seller: " . ($change->productPrice->seller->name ?? 'Unknown'));
                    $this->line("  Old Price: ₺{$change->old_price}");
                    $this->line("  New Price: ₺{$change->new_price}");
                    $this->line("  Drop: {$decreasePercentage}%");
                    
                    if (!$dryRun) {
                        // Dispatch alert job
                        SendPriceAlertJob::dispatch(
                            $change->productPrice,
                            $change->old_price,
                            $change->new_price
                        );
                        $alertsSent++;
                        $this->line("  ✓ Alert job dispatched");
                    } else {
                        $this->line("  → Would send alert (dry run)");
                    }
                    
                    $this->line("");
                }
            }

            $this->info("Monitoring completed:");
            $this->info("  Total changes analyzed: {$priceChanges->count()}");
            $this->info("  Significant drops: {$significantDrops}");
            $this->info("  Alerts " . ($dryRun ? "would be sent" : "sent") . ": " . ($dryRun ? $significantDrops : $alertsSent));

            Log::info('Price monitoring completed', [
                'hours' => $hours,
                'threshold' => $threshold,
                'total_changes' => $priceChanges->count(),
                'significant_drops' => $significantDrops,
                'alerts_sent' => $alertsSent,
                'dry_run' => $dryRun
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Price monitoring failed: " . $e->getMessage());
            Log::error('Price monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
