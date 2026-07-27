<?php

require_once __DIR__ . '/../../datapizza/orchestration/subagent_runner.php';
require_once __DIR__ . '/../../datapizza/loop/loop_evaluator.php';

function expect_true($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function completed_handover($task) {
    return [
        'task_id' => $task['task_id'],
        'status' => 'completed',
        'summary' => 'Offline review completed.',
        'evidence' => [],
        'open_questions' => [],
        'provenance' => ['mode' => 'offline_test']
    ];
}

function mismatched_handover($task) {
    return [
        'task_id' => 'wrong_task',
        'status' => 'completed',
        'summary' => 'This handover must be rejected.'
    ];
}

function make_contract($task_id) {
    return new TaskContract([
        'task_id' => $task_id,
        'goal' => 'Review bounded evidence.',
        'instructions' => 'Return a structured handover.',
        'context' => [],
        'allowed_tools' => []
    ]);
}

$trace = new LoopTrace();
$runner = new SubAgentRunner([
    'reviewer' => 'completed_handover'
], new LoopPolicy([
    'max_delegations' => 1
]), $trace);

$handover = $runner->delegate('reviewer', make_contract('task_001'));
expect_true($handover->get_status() === 'completed', 'Expected completed handover');

$budget_rejected = false;
try {
    $runner->delegate('reviewer', make_contract('task_002'));
} catch (RuntimeException $e) {
    $budget_rejected = $e->getMessage() === 'Delegation budget exhausted';
}
expect_true($budget_rejected, 'Expected delegation budget stop');

$unknown_role_rejected = false;
try {
    $runner->delegate('builder', make_contract('task_003'));
} catch (InvalidArgumentException $e) {
    $unknown_role_rejected = true;
}
expect_true($unknown_role_rejected, 'Expected unknown role rejection');

$mismatch_trace = new LoopTrace();
$mismatch_runner = new SubAgentRunner([
    'reviewer' => 'mismatched_handover'
], new LoopPolicy(), $mismatch_trace);

$mismatch_rejected = false;
try {
    $mismatch_runner->delegate('reviewer', make_contract('task_004'));
} catch (RuntimeException $e) {
    $mismatch_rejected = true;
}
expect_true($mismatch_rejected, 'Expected mismatched task rejection');

$evaluation = (new LoopEvaluator())->evaluate($mismatch_runner->get_trace(), [
    'required_role' => 'reviewer',
    'max_failures' => 1
]);
expect_true($evaluation['passed'], 'Expected failure trace evaluation');

echo "Task contract, handover, role, budget, and trace tests passed.\n";
