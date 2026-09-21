<?php

namespace App\Console\Commands;

use App\Services\RecurringOrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringOrders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'orders:process-recurring {--date= : Optional reference date (YYYY-MM-DD) to evaluate due recurring orders}';

    /**
     * The console command description.
     */
    protected $description = 'Evaluate and generate normal delivery orders for all active recurring schedules that are due';

    /**
     * Execute the console command.
     */
    public function handle(RecurringOrderService $recurringOrderService): int
    {
        $dateOption = $this->option('date');
        $asOfDate = $dateOption ? Carbon::parse($dateOption)->startOfDay() : now()->startOfDay();

        $this->info("Evaluating due recurring orders as of {$asOfDate->toDateString()}...");

        $createdOrders = $recurringOrderService->generateDueOrders($asOfDate);

        if ($createdOrders->isEmpty()) {
            $this->info("No recurring orders were due for generation.");
            return Command::SUCCESS;
        }

        $this->info("Successfully generated {$createdOrders->count()} order(s) from recurring schedules:");

        foreach ($createdOrders as $order) {
            $this->line(sprintf(
                "  - Order #%d: %s | %s | Total: ₱%.2f | Delivery Address: %s",
                $order->id,
                $order->customer_name,
                $order->breakdown,
                $order->total_amount,
                $order->delivery_address
            ));
        }

        return Command::SUCCESS;
    }
}
