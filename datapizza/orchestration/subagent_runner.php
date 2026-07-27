<?php

/**
 * 🍕 Datapizza-AI PHP - Sub-Agent Runner
 *
 * A small manager-style delegation boundary. Registered sub-agents are plain
 * PHP callables. The runner limits delegation count, records visible events,
 * and requires every sub-agent to return an AgentHandover.
 */
require_once __DIR__ . '/../loop/loop_policy.php';
require_once __DIR__ . '/../loop/loop_trace.php';
require_once __DIR__ . '/task_contract.php';
require_once __DIR__ . '/agent_handover.php';

class SubAgentRunner {
    private $agents;
    private $policy;
    private $trace;
    private $delegations = 0;

    public function __construct($agents, $policy = null, $trace = null) {
        if (!is_array($agents) || empty($agents)) {
            throw new InvalidArgumentException('At least one sub-agent is required');
        }

        foreach ($agents as $role => $agent) {
            if (!is_string($role)
                || !preg_match('/^[a-z][a-z0-9_]*$/', $role)
                || !is_callable($agent)) {
                throw new InvalidArgumentException('Invalid sub-agent registry');
            }
        }

        $this->agents = $agents;
        $this->policy = $policy ?: new LoopPolicy();
        $this->trace = $trace ?: new LoopTrace();
    }

    public function delegate($role, $contract) {
        if (!is_string($role) || !isset($this->agents[$role])) {
            throw new InvalidArgumentException("Unknown sub-agent role: $role");
        }
        if (!($contract instanceof TaskContract)) {
            throw new InvalidArgumentException('delegate expects a TaskContract');
        }
        if ($this->delegations >= $this->policy->get_max_delegations()) {
            $this->trace->record('run_stopped', [
                'reason' => 'delegation_budget'
            ]);
            throw new RuntimeException('Delegation budget exhausted');
        }

        $this->delegations++;
        $this->trace->record('delegation_started', [
            'role' => $role,
            'task_id' => $contract->get_task_id(),
            'number' => $this->delegations
        ]);

        try {
            $handover = call_user_func($this->agents[$role], $contract->to_array());
            if (is_array($handover)) {
                $handover = new AgentHandover($handover);
            }
            if (!($handover instanceof AgentHandover)) {
                throw new RuntimeException('Sub-agent must return an AgentHandover');
            }
            if ($handover->get_task_id() !== $contract->get_task_id()) {
                throw new RuntimeException('Handover task_id does not match task contract');
            }

            $this->trace->record('delegation_completed', [
                'role' => $role,
                'task_id' => $handover->get_task_id(),
                'status' => $handover->get_status()
            ]);
            return $handover;
        } catch (Throwable $e) {
            $this->trace->record('delegation_failed', [
                'role' => $role,
                'task_id' => $contract->get_task_id(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function stop($reason) {
        $this->trace->record('run_stopped', [
            'reason' => (string) $reason
        ]);
    }

    public function get_trace() {
        return $this->trace->all();
    }
}
