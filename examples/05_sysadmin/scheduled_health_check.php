<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This runner is available only from the CLI.\n");
    exit(64);
}

require_once __DIR__ . '/../../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../../datapizza/tools/disk_space.php';
require_once __DIR__ . '/../../datapizza/tools/system_uptime.php';
require_once __DIR__ . '/../../datapizza/tools/log_grep.php';
require_once __DIR__ . '/../../datapizza/loop/scheduled_run_guard.php';

$env_file = __DIR__ . '/../../.env';
if (is_file($env_file)) {
    $env = parse_ini_file($env_file);
    if (is_array($env)) {
        foreach ($env as $key => $value) {
            if (getenv($key) === false || getenv($key) === '') {
                putenv("$key=$value");
            }
        }
    }
}

$runtime_directory = __DIR__ . '/runtime';
if (!is_dir($runtime_directory) && !mkdir($runtime_directory, 0700, true)) {
    fwrite(STDERR, "Could not create the private runtime directory.\n");
    exit(73);
}

$guard = new ScheduledRunGuard(
    $runtime_directory . '/health-check.lock',
    $runtime_directory . '/health-check-status.json'
);

if (!$guard->acquire()) {
    fwrite(STDOUT, "SKIPPED: another health-check run is active.\n");
    exit(75);
}

$started_at = gmdate('c');
$guard->write_status('running', ['started_at' => $started_at]);

try {
    $log_file = getenv('SYSADMIN_DEMO_LOG');
    $resolved_log = $log_file === false ? false : realpath($log_file);
    if ($resolved_log === false || !is_file($resolved_log)) {
        throw new RuntimeException('SYSADMIN_DEMO_LOG must name an existing approved demo log');
    }

    $tools = [
        new DiskSpaceTool(['/']),
        new SystemUptimeTool(),
        new LogGrepTool(dirname($resolved_log))
    ];
    $task = 'Perform the read-only host health check. Inspect disk capacity and uptime. '
        . 'Search ' . basename($resolved_log) . ' for the literal text ERROR with at most 20 matches. '
        . 'Report only the evidence returned by the tools and state every limit.';

    $agent = new ReactAgent('openai', 'gpt-4o-mini', $tools, 5, false);
    $response = $agent->run($task);

    $stop_reason = null;
    foreach (array_reverse($agent->get_loop_trace()->all()) as $event) {
        if ($event['type'] === 'run_stopped') {
            $stop_reason = $event['data']['reason'] ?? null;
            break;
        }
    }

    $guard->write_status('completed', [
        'started_at' => $started_at,
        'response_length' => strlen($response),
        'stop_reason' => $stop_reason
    ]);
    fwrite(STDOUT, $response . PHP_EOL);
    exit(0);
} catch (Throwable $error) {
    $guard->write_status('failed', [
        'started_at' => $started_at,
        'error_type' => get_class($error)
    ]);
    fwrite(STDERR, 'FAILED: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
