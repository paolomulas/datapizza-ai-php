<?php

/**
 * 🍕 Datapizza-AI PHP - Loop Policy
 *
 * Keeps stopping conditions outside the prompt.
 * The policy does not authorize tools. It only limits how long a loop may
 * continue and how much observation text can return to an agent.
 */
class LoopPolicy {
    private $max_iterations;
    private $max_delegations;
    private $max_repeated_actions;
    private $max_observation_chars;
    private $max_elapsed_seconds;

    public function __construct($options = []) {
        $this->max_iterations = $this->positive_int(
            $options['max_iterations'] ?? 5,
            'max_iterations'
        );
        $this->max_delegations = $this->positive_int(
            $options['max_delegations'] ?? 3,
            'max_delegations'
        );
        $this->max_repeated_actions = $this->positive_int(
            $options['max_repeated_actions'] ?? 2,
            'max_repeated_actions'
        );
        $this->max_observation_chars = $this->positive_int(
            $options['max_observation_chars'] ?? 5000,
            'max_observation_chars'
        );

        $elapsed = $options['max_elapsed_seconds'] ?? 0;
        if (!is_int($elapsed) || $elapsed < 0) {
            throw new InvalidArgumentException(
                'max_elapsed_seconds must be a non-negative integer'
            );
        }
        $this->max_elapsed_seconds = $elapsed;
    }

    public function get_max_iterations() {
        return $this->max_iterations;
    }

    public function get_max_delegations() {
        return $this->max_delegations;
    }

    public function get_max_repeated_actions() {
        return $this->max_repeated_actions;
    }

    public function get_max_observation_chars() {
        return $this->max_observation_chars;
    }

    public function get_max_elapsed_seconds() {
        return $this->max_elapsed_seconds;
    }

    public function limit_observation($observation) {
        $observation = (string) $observation;
        $limit = $this->max_observation_chars;

        if (strlen($observation) <= $limit) {
            return [
                'content' => $observation,
                'truncated' => false,
                'original_length' => strlen($observation)
            ];
        }

        return [
            'content' => substr($observation, 0, $limit),
            'truncated' => true,
            'original_length' => strlen($observation)
        ];
    }

    private function positive_int($value, $name) {
        if (!is_int($value) || $value < 1) {
            throw new InvalidArgumentException("$name must be a positive integer");
        }
        return $value;
    }
}
