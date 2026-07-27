<?php

/**
 * 🍕 Datapizza-AI PHP - Loop Trace
 *
 * Records visible application events. It does not record hidden model
 * reasoning. The caller decides which prompts, observations, and outputs are
 * safe to retain.
 */
class LoopTrace {
    private $events = [];
    private $jsonl_path;
    private $redactor;

    public function __construct($jsonl_path = null, $redactor = null) {
        if ($jsonl_path !== null && (!is_string($jsonl_path) || trim($jsonl_path) === '')) {
            throw new InvalidArgumentException('jsonl_path must be a non-empty string or null');
        }
        if ($redactor !== null && !is_callable($redactor)) {
            throw new InvalidArgumentException('redactor must be callable or null');
        }

        $this->jsonl_path = $jsonl_path;
        $this->redactor = $redactor;
    }

    public function reset() {
        $this->events = [];
        if ($this->jsonl_path !== null && file_exists($this->jsonl_path)) {
            if (!unlink($this->jsonl_path)) {
                throw new RuntimeException('Unable to reset trace file');
            }
        }
    }

    public function record($type, $data = []) {
        if (!is_string($type) || !preg_match('/^[a-z][a-z0-9_]*$/', $type)) {
            throw new InvalidArgumentException('Trace event type must use snake_case');
        }
        if (!is_array($data)) {
            throw new InvalidArgumentException('Trace event data must be an array');
        }

        $event = [
            'sequence' => count($this->events) + 1,
            'type' => $type,
            'data' => $this->redact($data)
        ];
        $this->events[] = $event;

        if ($this->jsonl_path !== null) {
            $this->append_jsonl($event);
        }

        return $event;
    }

    public function all() {
        return $this->events;
    }

    private function redact($value) {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $item) {
                $clean[$key] = $this->redact($item);
            }
            return $clean;
        }

        if (is_string($value) && $this->redactor !== null) {
            return call_user_func($this->redactor, $value);
        }

        return $value;
    }

    private function append_jsonl($event) {
        $directory = dirname($this->jsonl_path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create trace directory');
        }

        $json = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Unable to encode trace event');
        }

        if (file_put_contents($this->jsonl_path, $json . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Unable to write trace event');
        }
    }
}
