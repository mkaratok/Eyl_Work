<?php

namespace App\Jobs;

use App\Services\PriceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BulkPriceUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $sellerId;
    protected array $priceUpdates;
    protected string $jobId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(int $sellerId, array $priceUpdates, string $jobId = null)
    {
        $this->sellerId = $sellerId;
        $this->priceUpdates = $priceUpdates;
        $this->jobId = $jobId ?? uniqid('bulk_price_', true);
    }

    /**
     * Execute the job.
     */
    public function handle(PriceService $priceService): void
    {
        try {
            Log::info('Starting bulk price update job', [
                'job_id' => $this->jobId,
                'seller_id' => $this->sellerId,
                'total_updates' => count($this->priceUpdates)
            ]);

            // Update job status in cache
            $this->updateJobStatus('processing', 0);

            // Process updates in chunks to avoid memory issues
            $chunks = array_chunk($this->priceUpdates, 50);
            $totalProcessed = 0;
            $totalSuccess = 0;
            $totalErrors = 0;
            $allErrors = [];

            foreach ($chunks as $chunkIndex => $chunk) {
                try {
                    $result = $priceService->bulkUpdatePrices($this->sellerId, $chunk);
                    
                    $totalSuccess += $result['success_count'];
                    $totalErrors += $result['error_count'];
                    $allErrors = array_merge($allErrors, $result['errors']);
                    $totalProcessed += count($chunk);

                    // Update progress
                    $progress = ($totalProcessed / count($this->priceUpdates)) * 100;
                    $this->updateJobStatus('processing', $progress, [
                        'processed' => $totalProcessed,
                        'success' => $totalSuccess,
                        'errors' => $totalErrors
                    ]);

                    Log::info('Processed bulk price update chunk', [
                        'job_id' => $this->jobId,
                        'chunk' => $chunkIndex + 1,
                        'chunk_size' => count($chunk),
                        'chunk_success' => $result['success_count'],
                        'chunk_errors' => $result['error_count'],
                        'total_progress' => round($progress, 2) . '%'
                    ]);

                } catch (\Exception $e) {
                    Log::error('Error processing bulk price update chunk', [
                        'job_id' => $this->jobId,
                        'chunk' => $chunkIndex + 1,
                        'error' => $e->getMessage()
                    ]);

                    // Add chunk errors
                    foreach ($chunk as $update) {
                        $allErrors[] = [
                            'product_id' => $update['product_id'] ?? null,
                            'error' => 'Chunk processing failed: ' . $e->getMessage()
                        ];
                        $totalErrors++;
                    }
                }
            }

            // Final job status
            $finalResult = [
                'total_processed' => count($this->priceUpdates),
                'success_count' => $totalSuccess,
                'error_count' => $totalErrors,
                'errors' => $allErrors,
                'completed_at' => now()->toISOString()
            ];

            $this->updateJobStatus('completed', 100, $finalResult);

            Log::info('Bulk price update job completed', [
                'job_id' => $this->jobId,
                'seller_id' => $this->sellerId,
                'result' => $finalResult
            ]);

        } catch (\Exception $e) {
            $this->updateJobStatus('failed', 0, [
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString()
            ]);

            Log::error('Bulk price update job failed', [
                'job_id' => $this->jobId,
                'seller_id' => $this->sellerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Update job status in cache
     */
    private function updateJobStatus(string $status, float $progress, array $data = []): void
    {
        $jobStatus = [
            'job_id' => $this->jobId,
            'seller_id' => $this->sellerId,
            'status' => $status,
            'progress' => $progress,
            'started_at' => $this->job->created_at ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
            ...$data
        ];

        // Store job status with 1 hour expiration
        Cache::put("bulk_price_job_{$this->jobId}", $jobStatus, 3600);
        
        // Also store by seller for easy lookup
        $sellerJobs = Cache::get("seller_bulk_jobs_{$this->sellerId}", []);
        $sellerJobs[$this->jobId] = $jobStatus;
        Cache::put("seller_bulk_jobs_{$this->sellerId}", $sellerJobs, 3600);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateJobStatus('failed', 0, [
            'error' => $exception->getMessage(),
            'failed_at' => now()->toISOString()
        ]);

        Log::error('BulkPriceUpdateJob failed', [
            'job_id' => $this->jobId,
            'seller_id' => $this->sellerId,
            'total_updates' => count($this->priceUpdates),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'bulk-price-update',
            "seller:{$this->sellerId}",
            "job:{$this->jobId}"
        ];
    }
}
