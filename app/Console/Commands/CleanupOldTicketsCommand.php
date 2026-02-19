<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupOldTicketsCommand extends Command
{
    protected $signature = 'tickets:cleanup';

    protected $description = 'Mark tickets as stale after 1 week and delete after 2 weeks';

    public function handle(): int
    {
        $this->markStaleTickets();
        $this->deleteOldTickets();

        return Command::SUCCESS;
    }

    private function markStaleTickets(): void
    {
        $staleDate = Carbon::now()->subWeek();

        $count = Ticket::where('status', '!=', 'closed')
            ->where('is_stale', false)
            ->where('updated_at', '<', $staleDate)
            ->update(['is_stale' => true]);

        if ($count > 0) {
            $this->info("Marked {$count} tickets as stale.");
        }
    }

    private function deleteOldTickets(): void
    {
        $deleteDate = Carbon::now()->subWeeks(2);

        $count = Ticket::where('updated_at', '<', $deleteDate)
            ->where('status', 'closed')
            ->delete();

        if ($count > 0) {
            $this->info("Deleted {$count} old closed tickets.");
        }
    }
}
