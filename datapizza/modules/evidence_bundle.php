<?php

/**
 * Build model-readable context and an application-readable source map from the
 * same retrieval results. The two views share stable labels such as [1].
 */
function evidence_build_bundle($results) {
    if (!is_array($results)) {
        throw new InvalidArgumentException('Retrieval results must be an array');
    }

    $context_parts = [];
    $source_map = [];

    foreach ($results as $index => $result) {
        if (!is_array($result) || !isset($result['text'])) {
            throw new InvalidArgumentException('Each result must contain text');
        }

        $number = $index + 1;
        $label = '[' . $number . ']';
        $text = trim((string) $result['text']);
        $context_parts[] = $label . ' ' . $text;
        $source_map[$label] = [
            'id' => $result['id'] ?? null,
            'score' => isset($result['score']) ? (float) $result['score'] : null,
            'metadata' => isset($result['metadata']) && is_array($result['metadata'])
                ? $result['metadata']
                : []
        ];
    }

    return [
        'context' => implode("\n\n", $context_parts),
        'source_map' => $source_map
    ];
}

/**
 * Check whether bracketed numeric citations in an answer refer to labels that
 * exist in the source map. This validates references, not factual entailment.
 */
function evidence_check_citations($answer, $source_map) {
    if (!is_string($answer) || !is_array($source_map)) {
        throw new InvalidArgumentException('Answer and source map have invalid types');
    }

    preg_match_all('/\[(\d+)\]/', $answer, $matches);
    $cited = array_values(array_unique($matches[0]));
    $invalid = [];

    foreach ($cited as $label) {
        if (!array_key_exists($label, $source_map)) {
            $invalid[] = $label;
        }
    }

    return [
        'cited' => $cited,
        'invalid' => $invalid,
        'has_citations' => count($cited) > 0,
        'all_citations_exist' => count($invalid) === 0
    ];
}
