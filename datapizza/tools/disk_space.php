<?php

require_once __DIR__ . '/base_tool.php';

/**
 * Read-only disk capacity inspection over constructor-approved paths.
 */
class DiskSpaceTool extends BaseTool {
    private $allowed_paths = [];

    public function __construct($allowed_paths = null) {
        $this->name = 'disk_space';
        $this->description = 'Returns total, free, used bytes and used percentage for an approved filesystem path.';

        $paths = $allowed_paths ?: [DIRECTORY_SEPARATOR];
        if (!is_array($paths) || empty($paths)) {
            throw new InvalidArgumentException('DiskSpaceTool requires at least one allowed path');
        }

        foreach ($paths as $path) {
            $resolved = realpath($path);
            if ($resolved === false || !is_dir($resolved)) {
                throw new InvalidArgumentException("Invalid allowed disk path: $path");
            }
            $this->allowed_paths[$this->normalize($resolved)] = $resolved;
        }
    }

    public function execute($params = []) {
        $requested = isset($params['path']) ? (string) $params['path'] : reset($this->allowed_paths);
        $resolved = realpath($requested);
        if ($resolved === false || !isset($this->allowed_paths[$this->normalize($resolved)])) {
            return 'Error: path is not in the approved disk-space allowlist';
        }

        $total = @disk_total_space($resolved);
        $free = @disk_free_space($resolved);
        if ($total === false || $free === false || $total <= 0) {
            return 'Error: disk capacity is unavailable for the approved path';
        }

        $used = $total - $free;
        return json_encode([
            'path' => $resolved,
            'total_bytes' => (int) $total,
            'free_bytes' => (int) $free,
            'used_bytes' => (int) $used,
            'used_percent' => round(($used / $total) * 100, 2)
        ], JSON_UNESCAPED_SLASHES);
    }

    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Filesystem path; it must exactly match a constructor-approved path'
                ]
            ]
        ];
    }

    private function normalize($path) {
        $path = rtrim($path, "/\\");
        if ($path === '') {
            $path = DIRECTORY_SEPARATOR;
        }
        return DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path;
    }
}
