<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AgentConfiguration;
use Illuminate\Console\Command;

class ResetDailySpendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:reset-daily-spend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset daily_spend to 0 for all agent configurations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = AgentConfiguration::query()->update(['daily_spend' => 0]);

        $this->info("Reset daily_spend for {$count} agent configurations.");

        return self::SUCCESS;
    }
}
