<?php

require_once __DIR__ . '/../../../datapizza/modules/evidence_bundle.php';

function evidence_expect($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$results = [
    [
        'id' => 'doc-a',
        'text' => 'The loop stops after its configured iteration limit.',
        'score' => 0.91,
        'metadata' => ['source' => 'agent-guide.md', 'chunk' => 2]
    ],
    [
        'id' => 'doc-b',
        'text' => 'Tool observations return to the model as messages.',
        'score' => 0.84,
        'metadata' => ['source' => 'tool-guide.md', 'chunk' => 4]
    ]
];

$bundle = evidence_build_bundle($results);
evidence_expect(strpos($bundle['context'], '[1] The loop stops') === 0, 'Missing context label');
evidence_expect($bundle['source_map']['[1]']['id'] === 'doc-a', 'Source id was not preserved');
evidence_expect(
    $bundle['source_map']['[2]']['metadata']['source'] === 'tool-guide.md',
    'Source metadata was not preserved'
);

$valid = evidence_check_citations('The loop is bounded [1], and observations return [2].', $bundle['source_map']);
evidence_expect($valid['has_citations'], 'Expected citations');
evidence_expect($valid['all_citations_exist'], 'Valid citations were rejected');

$invalid = evidence_check_citations('This answer cites a missing item [3].', $bundle['source_map']);
evidence_expect(!$invalid['all_citations_exist'], 'Missing citation was accepted');
evidence_expect($invalid['invalid'] === ['[3]'], 'Wrong invalid citation list');

$uncited = evidence_check_citations('This answer contains no source label.', $bundle['source_map']);
evidence_expect(!$uncited['has_citations'], 'Uncited answer was reported as cited');
evidence_expect($uncited['all_citations_exist'], 'No citations should not create invalid labels');

echo "Evidence context, source map, and citation reference checks passed.\n";
