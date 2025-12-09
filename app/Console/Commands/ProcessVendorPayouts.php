<?php

namespace App\Console\Commands;

use App\Services\PayoutService;
use Illuminate\Console\Command;

class ProcessVendorPayouts extends Command
{
    protected $signature = 'payouts:process';
    protected $description = 'Process weekly vendor payouts';

    public function __construct(
        protected PayoutService $payoutService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Processing vendor payouts...');

        try {
            $results = $this->payoutService->processWeeklyPayouts();

            $this->info("Processed {$results['total_vendors']} vendors");
            $this->info("Total amount: Rp " . number_format($results['total_amount'], 0, ',', '.'));
            $this->info("Payouts created: {$results['payouts_created']}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process payouts: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
