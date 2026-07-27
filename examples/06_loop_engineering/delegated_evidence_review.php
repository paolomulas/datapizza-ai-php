<?php

/**
 * Example 6: Delegated Evidence Review
 *
 * This offline example demonstrates manager-style loop engineering:
 * - the coordinator creates a bounded task contract;
 * - a reviewer sub-agent receives only the supplied evidence;
 * - the reviewer returns a structured handover;
 * - policy and trace remain under coordinator control.
 *
 * No provider call is made. Replace the scripted reviewer only after the
 * delegation boundary is understood and tested.
 */
require_once __DIR__ . '/../../datapizza/orchestration/subagent_runner.php';
require_once __DIR__ . '/../../datapizza/loop/loop_evaluator.php';

function evidence_reviewer($task) {
    $items = $task['context']['evidence'] ?? [];
    $supported = [];
    $open_questions = [];

    foreach ($items as $item) {
        if (!isset($item['source'], $item['text'])) {
            $open_questions[] = 'One evidence item is missing source or text.';
            continue;
        }
        $supported[] = [
            'source' => $item['source'],
            'claim' => $item['text']
        ];
    }

    return new AgentHandover([
        'task_id' => $task['task_id'],
        'status' => empty($open_questions) ? 'completed' : 'needs_review',
        'summary' => count($supported) . ' evidence item(s) reviewed.',
        'evidence' => $supported,
        'open_questions' => $open_questions,
        'next_action' => 'Coordinator decides whether the evidence is sufficient.',
        'provenance' => [
            'role' => 'reviewer',
            'mode' => 'offline_script'
        ]
    ]);
}

$policy = new LoopPolicy([
    'max_delegations' => 1,
    'max_iterations' => 2
]);
$trace = new LoopTrace();
$runner = new SubAgentRunner([
    'reviewer' => 'evidence_reviewer'
], $policy, $trace);

$contract = new TaskContract([
    'task_id' => 'review_001',
    'goal' => 'Review the supplied evidence before the final answer.',
    'instructions' => 'Keep sources attached and report missing fields.',
    'context' => [
        'evidence' => [
            [
                'source' => 'health-check',
                'text' => 'Disk usage is below the configured warning threshold.'
            ]
        ]
    ],
    'allowed_tools' => []
]);

$handover = $runner->delegate('reviewer', $contract);
$runner->stop('completed');

$evaluation = (new LoopEvaluator())->evaluate($runner->get_trace(), [
    'stop_reason' => 'completed',
    'required_role' => 'reviewer',
    'max_failures' => 0
]);

echo json_encode([
    'handover' => $handover->to_array(),
    'evaluation' => $evaluation,
    'trace' => $runner->get_trace()
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
