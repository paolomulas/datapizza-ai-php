<?php

/**
 * 🍕 Datapizza-AI PHP - Task Contract
 *
 * Defines the small context capsule given to a sub-agent.
 * A task contract is not a full conversation and does not grant tool access by
 * itself. The coordinator remains responsible for authorization.
 */
class TaskContract {
    private $task_id;
    private $goal;
    private $instructions;
    private $context;
    private $allowed_tools;

    public function __construct($options) {
        if (!is_array($options)) {
            throw new InvalidArgumentException('Task contract must be an array');
        }

        $this->task_id = $this->required_text($options, 'task_id');
        if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/', $this->task_id)) {
            throw new InvalidArgumentException('Invalid task_id');
        }

        $this->goal = $this->required_text($options, 'goal');
        $this->instructions = $this->required_text($options, 'instructions');
        $this->context = $options['context'] ?? [];
        $this->allowed_tools = $options['allowed_tools'] ?? [];

        if (!is_array($this->context) || !is_array($this->allowed_tools)) {
            throw new InvalidArgumentException('context and allowed_tools must be arrays');
        }

        foreach ($this->allowed_tools as $tool) {
            if (!is_string($tool) || !preg_match('/^[A-Za-z0-9_-]+$/', $tool)) {
                throw new InvalidArgumentException('Invalid allowed tool name');
            }
        }
    }

    public function get_task_id() {
        return $this->task_id;
    }

    public function to_array() {
        return [
            'task_id' => $this->task_id,
            'goal' => $this->goal,
            'instructions' => $this->instructions,
            'context' => $this->context,
            'allowed_tools' => $this->allowed_tools
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
