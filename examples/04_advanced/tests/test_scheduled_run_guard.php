<?php

require_once __DIR__ . '/../../../datapizza/loop/scheduled_run_guard.php';

function scheduled_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'datapizza-schedule-' . bin2hex(random_bytes(4));
if (!mkdir($directory, 0700, true)) {
    throw new RuntimeException('Could not create schedule test directory');
}

$lock = $directory . DIRECTORY_SEPARATOR . 'run.lock';
$status = $directory . DIRECTORY_SEPARATOR . 'status.json';

try {
    $first = new ScheduledRunGuard($lock, $status);
    $second = new ScheduledRunGuard($lock, $status);

    scheduled_assert($first->acquire(), 'First runner should acquire the lock');
    scheduled_assert(!$second->acquire(), 'Second runner should skip while the lock is held');

    $first->write_status('running', ['task' => 'fixture-health-check']);
    $decoded = json_decode(file_get_contents($status), true);
    scheduled_assert($decoded['status'] === 'running', 'Status should be written atomically');
    scheduled_assert($decoded['task'] === 'fixture-health-check', 'Status should preserve details');

    $first->release();
    scheduled_assert($second->acquire(), 'A later runner should acquire the released lock');
    $second->release();

    echo "Scheduled-run overlap and status tests passed.\n";
} finally {
    @unlink($status);
    @unlink($lock);
    @rmdir($directory);
}
