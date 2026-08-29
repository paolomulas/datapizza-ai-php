<?php

/**
 * 🍕 Datapizza-AI PHP - Agent Handover
 *
 * Carries a bounded result from a sub-agent back to the coordinator.
 * Evidence and open questions stay separate from the human-readable summary.
 */
class AgentHandover {
    private $task_id;
    private $status;
    private $summary;
    private $evidence;
    private $open_questions;
    private $next_action;
    private $provenance;

    public function __construct($options) {
        if (!is_array($options)) {
            throw new InvalidArgumentException('Agent handover must be an array');
        }

        $this->task_id = $this->required_text($options, 'task_id');
        $this->status = $this->required_text($options, 'status');
        $this->summary = $this->required_text($options, 'summary');
        $this->evidence = $options['evidence'] ?? [];
        $this->open_questions = $options['open_questions'] ?? [];
        $this->next_action = isset($options['next_action'])
            ? trim((string) $options['next_action'])
            : '';
        $this->provenance = $options['provenance'] ?? [];

        $allowed_statuses = ['completed', 'needs_review', 'blocked', 'failed'];
        if (!in_array($this->status, $allowed_statuses, true)) {
            throw new InvalidArgumentException('Invalid handover status');
        }
        if (!is_array($this->evidence)
            || !is_array($this->open_questions)
            || !is_array($this->provenance)) {
            throw new InvalidArgumentException(
                'evidence, open_questions, and provenance must be arrays'
            );
        }
    }

    public function get_task_id() {
        return $this->task_id;
    }

    public function get_status() {
        return $this->status;
    }

    public function to_array() {
        return [
            'task_id' => $this->task_id,
            'status' => $this->status,
            'summary' => $this->summary,
            'evidence' => $this->evidence,
            'open_questions' => $this->open_questions,
            'next_action' => $this->next_action,
            'provenance' => $this->provenance
        ];
    }

    private function required_text($options, $name) {
        $value = $options[$name] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("$name is required");
        }
        return trim($value);
    }
}
