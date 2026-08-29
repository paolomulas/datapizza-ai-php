<?php

/**
 * 🍕 Datapizza-AI PHP - Loop Evaluator
 *
 * Runs deterministic checks over a visible loop trace.
 */
class LoopEvaluator {
    public function evaluate($events, $expectations) {
        if (!is_array($events) || !is_array($expectations)) {
            throw new InvalidArgumentException('Events and expectations must be arrays');
        }

        $types = [];
        $tools = [];
        $roles = [];
        $stop_reason = null;

        foreach ($events as $event) {
            if (!isset($event['type']) || !isset($event['data']) || !is_array($event['data'])) {
                throw new InvalidArgumentException('Malformed trace event');
            }
            $types[] = $event['type'];
            if ($event['type'] === 'tool_requested' && isset($event['data']['tool'])) {
                $tools[] = $event['data']['tool'];
            }
            if ($event['type'] === 'delegation_started' && isset($event['data']['role'])) {
                $roles[] = $event['data']['role'];
            }
            if ($event['type'] === 'run_stopped' && isset($event['data']['reason'])) {
                $stop_reason = $event['data']['reason'];
            }
        }

        $checks = [];
        if (isset($expectations['stop_reason'])) {
            $checks['stop_reason'] = $stop_reason === $expectations['stop_reason'];
        }
        if (isset($expectations['required_tool'])) {
            $checks['required_tool'] = in_array($expectations['required_tool'], $tools, true);
        }
        if (isset($expectations['forbidden_tool'])) {
            $checks['forbidden_tool'] = !in_array($expectations['forbidden_tool'], $tools, true);
        }
        if (isset($expectations['required_role'])) {
            $checks['required_role'] = in_array($expectations['required_role'], $roles, true);
        }
        if (isset($expectations['forbidden_role'])) {
            $checks['forbidden_role'] = !in_array($expectations['forbidden_role'], $roles, true);
        }
        if (isset($expectations['max_failures'])) {
            $actual = count(array_filter($types, function ($type) {
                return $type === 'delegation_failed';
            }));
            $checks['max_failures'] = $actual <= $expectations['max_failures'];
        }
        if (isset($expectations['max_parse_errors'])) {
            $actual = count(array_filter($types, function ($type) {
                return $type === 'parse_error';
            }));
            $checks['max_parse_errors'] = $actual <= $expectations['max_parse_errors'];
        }

        return [
            'passed' => !in_array(false, $checks, true),
            'checks' => $checks,
            'tool_calls' => count($tools),
            'delegations' => count($roles),
            'event_count' => count($events)
        ];
    }
}
