<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DayStatus;
use Carbon\Carbon;

class LockPreviousDays extends Command
{
    protected $signature = 'daystatus:lock';

    protected $description = 'Lock all previous day records';

    public function handle()
    {
        DayStatus::whereDate('date', '<', Carbon::today())
            ->update([
                'lock_flag' => 1
            ]);

        $this->info('Previous day records locked successfully.');

        return Command::SUCCESS;
    }
}