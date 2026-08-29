<?php

require_once __DIR__ . '/base_tool.php';

/**
 * Literal, bounded line search inside one approved log directory.
 */
class LogGrepTool extends BaseTool {
    const MAX_MATCHES = 50;
    const MAX_SCANNED_LINES = 10000;
    const MAX_PATTERN_LENGTH = 120;
    const MAX_RETURNED_LINE_LENGTH = 1000;

    private $allowed_directory;

    public function __construct($allowed_directory) {
        $resolved = realpath($allowed_directory);
        if ($resolved === false || !is_dir($resolved)) {
            throw new InvalidArgumentException('LogGrepTool requires an existing allowed directory');
        }

        $this->name = 'log_grep';
        $this->description = 'Searches for a literal case-insensitive pattern in one approved .log or .txt file with line and match caps.';
        $this->allowed_directory = $resolved;
    }

    public function execute($params = []) {
        if (!isset($params['filename']) || !isset($params['pattern'])) {
            return "Error: parameters 'filename' and 'pattern' are required";
        }

        $filename = basename((string) $params['filename']);
        $pattern = (string) $params['pattern'];
        if ($pattern === '' || strlen($pattern) > self::MAX_PATTERN_LENGTH) {
            return 'Error: pattern must contain between 1 and 120 bytes';
        }

        $max_matches = $params['max_matches'] ?? 20;
        if (filter_var($max_matches, FILTER_VALIDATE_INT) === false || (int) $max_matches < 1) {
            return 'Error: max_matches must be a positive integer';
        }
        $max_matches = min((int) $max_matches, self::MAX_MATCHES);

        $candidate = $this->allowed_directory . DIRECTORY_SEPARATOR . $filename;
        $resolved = realpath($candidate);
        if ($resolved === false || !is_file($resolved)
            || $this->normalize(dirname($resolved)) !== $this->normalize($this->allowed_directory)) {
            return 'Error: approved log file was not found';
        }

        $extension = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        if (!in_array($extension, ['log', 'txt'], true)) {
            return 'Error: only .log and .txt files are searchable';
        }

        $handle = @fopen($resolved, 'rb');
        if ($handle === false) {
            return 'Error: approved log file could not be opened';
        }

        $matches = [];
        $line_number = 0;
        $scan_capped = false;
        try {
            while (($line = fgets($handle, self::MAX_RETURNED_LINE_LENGTH + 2)) !== false) {
                $line_number++;
                if ($line_number > self::MAX_SCANNED_LINES) {
                    $scan_capped = true;
                    break;
                }

                $truncated = strpos($line, "\n") === false && !feof($handle);
                if ($truncated) {
                    while (($remainder = fgets($handle, self::MAX_RETURNED_LINE_LENGTH + 2)) !== false) {
                        if (strpos($remainder, "\n") !== false || feof($handle)) {
                            break;
                        }
                    }
                }
                if (stripos($line, $pattern) === false) {
                    continue;
                }

                $clean = rtrim($line, "\r\n");
                if (strlen($clean) > self::MAX_RETURNED_LINE_LENGTH) {
                    $clean = substr($clean, 0, self::MAX_RETURNED_LINE_LENGTH);
                    $truncated = true;
                }
                $matches[] = [
                    'line' => $line_number,
                    'text' => $clean,
                    'truncated' => $truncated
                ];
                if (count($matches) >= $max_matches) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return json_encode([
            'filename' => $filename,
            'pattern' => $pattern,
            'matches' => $matches,
            'match_count' => count($matches),
            'scanned_lines' => min($line_number, self::MAX_SCANNED_LINES),
            'scan_capped' => $scan_capped,
            'match_cap' => $max_matches,
            'line_prefix_cap' => self::MAX_RETURNED_LINE_LENGTH
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'filename' => ['type' => 'string', 'description' => 'Basename of an approved .log or .txt file'],
                'pattern' => ['type' => 'string', 'description' => 'Literal case-insensitive text to search for'],
                'max_matches' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => self::MAX_MATCHES,
                    'description' => 'Maximum matching lines to return'
                ]
            ],
            'required' => ['filename', 'pattern']
        ];
    }

    private function normalize($path) {
        $path = rtrim($path, "/\\");
        return DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path;
    }
}
