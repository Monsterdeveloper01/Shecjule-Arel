<?php

namespace App\Console\Commands;

use App\Services\PriorityEngine;
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
    public function handle(WebPushService $pushService, PriorityEngine $priorityEngine): int
    {
        $this->info('Recalculating task priorities...');
        $recalculated = $priorityEngine->recalculateAllAndPersist();
        $this->info("Recalculated {$recalculated} task(s).");

        $this->info('Checking deadlines and sending notifications...');

        $count = $pushService->checkAndSendDeadlineAlerts();

        $this->info("Completed. Sent {$count} notification(s).");

        return Command::SUCCESS;
    }
}
