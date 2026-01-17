<?php

namespace App\Console\Commands;

use App\Services\TaskReminderService;
use Illuminate\Console\Command;

class DispatchRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch due task reminders';

    /**
     * Execute the console command.
     */
    public function handle(TaskReminderService $reminderService)
    {
        $this->info('Checking reminders...');
        $reminderService->checkAndDispatchReminders();
        $this->info('Done.');
    }
}
