<?php

namespace App\Console\Commands;

use App\Models\LaunchpadProject;
use App\Services\LaunchpadService;
use Illuminate\Console\Command;

class ManageLaunchpad extends Command
{
    protected $signature = 'lozand:manage-launchpad';

    protected $description = 'Auto-start launchpad sales, finalize ended sales and enable trading when launch time is reached';

    public function handle(LaunchpadService $launchpad): int
    {
        $now = now();

        $projects = LaunchpadProject::query()
            ->where('approval_status', 'approved')
            ->where('is_visible', true)
            ->where('status', '!=', 'canceled')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($projects as $project) {
            try {
                $project->refresh();

                if ($project->status === 'draft') {
                    $startOk = !$project->sale_start_at || $now->gte($project->sale_start_at);
                    $endOk = !$project->sale_end_at || $now->lte($project->sale_end_at);
                    if ($startOk && $endOk) {
                        $project->update(['status' => 'live']);
                        $launchpad->ensureMarket($project);
                    }
                }

                $hardCap = (float) $project->hard_cap_quote;
                $sold = (float) $project->sold_quote;
                $capReached = $hardCap > 0 && $sold >= $hardCap;
                $saleEndedByTime = $project->sale_end_at && $now->gt($project->sale_end_at);

                if ($project->status === 'live' && ($capReached || $saleEndedByTime)) {
                    $launchpad->finalizeSale($project);
                }

                $launchOk = $project->launch_at && $now->gte($project->launch_at);

                if (!$project->trading_enabled && $launchOk) {
                    if ($project->status === 'live' && ($capReached || $saleEndedByTime || !$project->sale_end_at)) {
                        $launchpad->finalizeSale($project);
                        $project->refresh();
                    }

                    if (in_array($project->status, ['ended', 'launched'], true) || (bool) $project->trading_enabled) {
                        $launchpad->enableTrading($project);
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        updateLastCronJob($this->signature);
        return self::SUCCESS;
    }
}

