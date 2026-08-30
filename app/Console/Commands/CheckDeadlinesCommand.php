<?php

namespace App\Console\Commands;

use App\Services\WebPushService;
use Illuminate\Console\Command;

class CheckDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check upcoming and overdue deadlines and dispatch push notifications';

    /**
     * Execute the console command.
     */
    public function handle(WebPushService $pushService): int
    {
        $this->info('Checking deadlines and sending notifications...');

        $count = $pushService->checkAndSendDeadlineAlerts();

        $this->info("Completed. Sent {$count} notification(s).");

        return Command::SUCCESS;
    }
}
