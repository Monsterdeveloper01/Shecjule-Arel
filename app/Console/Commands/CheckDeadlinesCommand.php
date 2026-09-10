<?php

namespace App\Console\Commands;

use App\Services\DeadlineRiskEngine;
use App\Services\PriorityEngine;
use App\Services\SmartReminderService;
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
    public function handle(
        WebPushService $pushService,
        PriorityEngine $priorityEngine,
        DeadlineRiskEngine $riskEngine,
        SmartReminderService $smartReminderService
    ): int {
        $this->info('Recalculating task priorities and risks...');
        $recalculatedP = $priorityEngine->recalculateAllAndPersist();
        $recalculatedR = $riskEngine->recalculateAllAndPersist();
        $this->info("Recalculated {$recalculatedP} task priority scores and {$recalculatedR} deadline risk levels.");

        $this->info('Checking deadlines and sending notifications...');
        $count = $pushService->checkAndSendDeadlineAlerts();

        $this->info('Evaluating smart context-aware reminders...');
        $smartCount = $smartReminderService->dispatchSmartReminders();

        $this->info("Completed. Sent {$count} standard alert(s) and {$smartCount} smart reminder(s).");

        return Command::SUCCESS;
    }
}
