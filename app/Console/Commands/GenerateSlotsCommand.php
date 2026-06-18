<?php

namespace App\Console\Commands;

use App\Services\SlotGenerationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('slots:generate {--days=60 : Number of days ahead to generate slots}')]
#[Description('Generate time slots for all active vendors')]
class GenerateSlotsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SlotGenerationService $slotService)
    {
        $days = (int) $this->option('days');

        $this->info("Generating time slots for {$days} days ahead...");

        $total = $slotService->generateForAllVendors($days);

        $this->info("✓ Generated {$total} time slots successfully.");

        return self::SUCCESS;
    }
}
