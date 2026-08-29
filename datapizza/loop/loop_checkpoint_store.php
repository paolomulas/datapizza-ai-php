<?php

/**
 * 🍕 Datapizza-AI PHP - Loop Checkpoint Store
 *
 * Persists application state supplied by the caller. It does not serialize
 * provider clients, tool objects, closures, or credentials.
 */
class LoopCheckpointStore {
    private $directory;

    public function __construct($directory) {
        if (!is_string($directory) || trim($directory) === '') {
            throw new InvalidArgumentException('Checkpoint directory is required');
        }
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    public function save($run_id, $state) {
        $path = $this->path_for($run_id);
        if (!is_array($state)) {
            throw new InvalidArgumentException('Checkpoint state must be an array');
        }

        if (!is_dir($this->directory)
            && !mkdir($this->directory, 0775, true)
            && !is_dir($this->directory)) {
            throw new RuntimeException('Unable to create checkpoint directory');
        }

        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Unable to encode checkpoint state');
        }

        $temporary = $path . '.tmp.' . getmypid();
        if (file_put_contents($temporary, $json, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write temporary checkpoint');
        }
        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to publish checkpoint atomically');
        }

        return $path;
    }

    public function load($run_id) {
        $path = $this->path_for($run_id);
        if (!is_file($path)) {
            return null;
        }

        $json = file_get_contents($path);
        $state = json_decode($json, true);
        if (!is_array($state) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Checkpoint contains invalid JSON');
        }
        return $state;
    }

    public function delete($run_id) {
        $path = $this->path_for($run_id);
        return !file_exists($path) || unlink($path);
    }

    private function path_for($run_id) {
        if (!is_string($run_id) || !preg_match('/^[A-Za-z0-9_-]{1,80}$/', $run_id)) {
            throw new InvalidArgumentException('Invalid checkpoint run id');
        }
        return $this->directory . DIRECTORY_SEPARATOR . $run_id . '.json';
    }
}
