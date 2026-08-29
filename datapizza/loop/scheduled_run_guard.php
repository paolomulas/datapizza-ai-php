<?php

/**
 * Non-blocking overlap guard and atomic status writer for scheduled CLI runs.
 */
class ScheduledRunGuard {
    private $lock_file;
    private $status_file;
    private $handle = null;

    public function __construct($lock_file, $status_file) {
        $this->lock_file = $this->validate_target($lock_file, 'lock');
        $this->status_file = $this->validate_target($status_file, 'status');
    }

    public function acquire() {
        if (is_resource($this->handle)) {
            return true;
        }

        $handle = @fopen($this->lock_file, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Could not open the scheduled-run lock file');
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode([
            'pid' => getmypid(),
            'acquired_at' => gmdate('c')
        ], JSON_UNESCAPED_SLASHES));
        fflush($handle);
        $this->handle = $handle;
        return true;
    }

    public function write_status($status, $details = []) {
        $payload = array_merge($details, [
            'status' => (string) $status,
            'recorded_at' => gmdate('c')
        ]);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Scheduled-run status could not be encoded');
        }

        $temporary = $this->status_file . '.tmp.' . getmypid();
        if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false
            || !rename($temporary, $this->status_file)) {
            @unlink($temporary);
            throw new RuntimeException('Scheduled-run status could not be written');
        }
    }

    public function release() {
        if (!is_resource($this->handle)) {
            return;
        }
        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = null;
    }

    public function __destruct() {
        $this->release();
    }

    private function validate_target($path, $label) {
        $path = (string) $path;
        $directory = realpath(dirname($path));
        if ($path === '' || $directory === false || !is_dir($directory)) {
            throw new InvalidArgumentException("Scheduled-run $label directory must exist");
        }
        return $directory . DIRECTORY_SEPARATOR . basename($path);
    }
}
