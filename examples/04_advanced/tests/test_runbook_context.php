<?php

require_once __DIR__ . '/../../../datapizza/modules/runbook_context.php';

function runbook_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$results = [
    [
        'id' => 'demo-step-2',
        'text' => 'Confirm the bounded observation before continuing.',
        'score' => 0.81,
        'metadata' => [
            'procedure_id' => 'demo-health-check',
            'title' => 'Demonstration Health Check',
            'step' => 2,
            'kind' => 'verification'
        ]
    ],
    [
        'id' => 'demo-step-1',
        'text' => 'Collect a read-only observation from the approved tool.',
        'score' => 0.88,
        'metadata' => [
            'procedure_id' => 'demo-health-check',
            'title' => 'Demonstration Health Check',
            'step' => 1,
            'kind' => 'procedure'
        ]
    ]
];

$bundle = runbook_build_context($results);
runbook_assert(strpos($bundle['context'], 'step 1') < strpos($bundle['context'], 'step 2'), 'Steps should be ordered');
runbook_assert($bundle['procedure_ids'] === ['demo-health-check'], 'Procedure identity should be preserved');
runbook_assert($bundle['source_map']['[1]']['metadata']['kind'] === 'procedure', 'Source map should preserve metadata');
$citation_check = evidence_check_citations('Use [1], then verify with [2].', $bundle['source_map']);
runbook_assert($citation_check['all_citations_exist'], 'Runbook labels should be checkable');

$invalid = $results;
unset($invalid[0]['metadata']['kind']);
try {
    runbook_build_context($invalid);
    throw new RuntimeException('Missing runbook metadata should be rejected');
} catch (InvalidArgumentException $expected) {
}

echo "Runbook context structure and source-map tests passed.\n";
