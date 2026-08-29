<?php

require_once __DIR__ . '/base_tool.php';

/**
 * Linux uptime inspection through the bounded /proc/uptime text interface.
 */
class SystemUptimeTool extends BaseTool {
    private $source_file;

    public function __construct($source_file = '/proc/uptime') {
        $this->name = 'system_uptime';
        $this->description = 'Returns Linux uptime from a configured proc-style uptime file; it does not run a shell command.';
        $this->source_file = $source_file;
    }

    public function execute($params = []) {
        if (!is_file($this->source_file) || !is_readable($this->source_file)) {
            return 'Error: uptime source is unavailable';
        }

        $handle = @fopen($this->source_file, 'rb');
        if ($handle === false) {
            return 'Error: uptime source could not be opened';
        }
        try {
            $line = fgets($handle, 129);
        } finally {
            fclose($handle);
        }

        if (!is_string($line) || !preg_match('/^([0-9]+(?:\.[0-9]+)?)/', trim($line), $matches)) {
            return 'Error: uptime source has an unexpected format';
        }

        $seconds = (int) floor((float) $matches[1]);
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return json_encode([
            'uptime_seconds' => $seconds,
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'source' => basename($this->source_file)
        ], JSON_UNESCAPED_SLASHES);
    }

    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => []
        ];
    }
}
