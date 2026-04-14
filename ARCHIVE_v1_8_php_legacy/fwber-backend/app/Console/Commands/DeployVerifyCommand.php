<?php

namespace App\Console\Commands;

use App\Services\HealthStatusService;
use Illuminate\Console\Command;

class DeployVerifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:verify {--json : Output raw JSON instead of a human-readable table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify deployment-critical services and configuration before or after a production cutover';

    public function __construct(
        protected HealthStatusService $healthStatusService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $status = $this->healthStatusService->build(includeMetrics: true);

        if ($this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $status['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
        }

        $this->info('fwber deployment verification');
        $this->line('Environment: '.$status['environment']);
        $this->line('Version: '.$status['version']);
        $this->newLine();

        $rows = [];
        foreach ($status['checks'] as $name => $check) {
            $rows[] = [
                ucfirst($name),
                strtoupper($check['status'] ?? 'unknown'),
                $check['critical'] ?? false ? 'yes' : 'no',
                $check['error'] ?? $check['reason'] ?? ($check['driver'] ?? $check['connection'] ?? '—'),
            ];
        }

        $this->table(['Check', 'Status', 'Critical', 'Notes'], $rows);
        $this->newLine();
        $this->line('Memory usage: '.$status['metrics']['memory_usage']);
        $this->line('Peak memory: '.$status['metrics']['memory_peak']);
        $this->line('Approximate uptime: '.$status['metrics']['uptime']);

        if ($status['status'] === 'healthy') {
            $this->info('All critical checks passed.');

            return self::SUCCESS;
        }

        $this->error('One or more critical checks failed.');

        return self::FAILURE;
    }
}
