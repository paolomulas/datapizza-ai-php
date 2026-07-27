<?php

require_once __DIR__ . '/../../../datapizza/tools/disk_space.php';
require_once __DIR__ . '/../../../datapizza/tools/system_uptime.php';
require_once __DIR__ . '/../../../datapizza/tools/log_grep.php';

function sysadmin_expect($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$temporary = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'datapizza-sysadmin-' . uniqid('', true);
mkdir($temporary, 0775, true);
$uptime_file = $temporary . DIRECTORY_SEPARATOR . 'uptime';
$log_file = $temporary . DIRECTORY_SEPARATOR . 'auth.log';
$other_file = $temporary . DIRECTORY_SEPARATOR . 'binary.bin';

try {
    file_put_contents($uptime_file, "90061.42 120000.00\n", LOCK_EX);
    file_put_contents(
        $log_file,
        "Accepted publickey for alice\n" .
        "Failed password for root\n" .
        "failed password for admin\n" .
        "Session closed\n",
        LOCK_EX
    );
    file_put_contents($other_file, "Failed but unsupported extension\n", LOCK_EX);

    $disk = new DiskSpaceTool([$temporary]);
    $disk_result = json_decode($disk->execute(['path' => $temporary]), true);
    sysadmin_expect(is_array($disk_result), 'Disk result is not JSON');
    sysadmin_expect($disk_result['total_bytes'] > 0, 'Disk total is not positive');
    sysadmin_expect($disk_result['free_bytes'] >= 0, 'Disk free bytes are invalid');
    sysadmin_expect(
        $disk->execute(['path' => dirname($temporary)]) ===
            'Error: path is not in the approved disk-space allowlist',
        'Disk allowlist escape was not rejected'
    );

    $uptime = new SystemUptimeTool($uptime_file);
    $uptime_result = json_decode($uptime->execute(), true);
    sysadmin_expect($uptime_result['uptime_seconds'] === 90061, 'Wrong uptime seconds');
    sysadmin_expect($uptime_result['days'] === 1, 'Wrong uptime days');
    sysadmin_expect($uptime_result['hours'] === 1, 'Wrong uptime hours');
    sysadmin_expect($uptime_result['minutes'] === 1, 'Wrong uptime minutes');

    $logs = new LogGrepTool($temporary);
    $log_result = json_decode($logs->execute([
        'filename' => 'auth.log',
        'pattern' => 'Failed',
        'max_matches' => 1
    ]), true);
    sysadmin_expect($log_result['match_count'] === 1, 'Log match cap was not applied');
    sysadmin_expect($log_result['matches'][0]['line'] === 2, 'Wrong log line number');
    sysadmin_expect(
        $logs->execute(['filename' => '../auth.log', 'pattern' => 'Failed']) !== '',
        'Traversal-shaped input should be handled as a basename'
    );
    sysadmin_expect(
        $logs->execute(['filename' => 'binary.bin', 'pattern' => 'Failed']) ===
            'Error: only .log and .txt files are searchable',
        'Unsupported log extension was not rejected'
    );
    sysadmin_expect(
        strpos($logs->execute(['filename' => 'auth.log', 'pattern' => '']), 'Error:') === 0,
        'Empty pattern was not rejected'
    );

    echo "Disk, uptime, and bounded literal log-search tests passed.\n";
} finally {
    foreach ([$uptime_file, $log_file, $other_file] as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    if (is_dir($temporary)) {
        rmdir($temporary);
    }
}
