<?php

require_once __DIR__ . '/evidence_bundle.php';

/**
 * Validate retrieved runbook records and preserve their procedure structure.
 */
function runbook_build_context($results) {
    if (!is_array($results)) {
        throw new InvalidArgumentException('Runbook results must be an array');
    }

    $allowed_kinds = ['prerequisite', 'procedure', 'warning', 'verification', 'escalation'];
    $normalized = [];
    foreach ($results as $index => $result) {
        if (!is_array($result) || trim((string) ($result['text'] ?? '')) === '') {
            throw new InvalidArgumentException("Runbook result $index requires text");
        }

        $metadata = $result['metadata'] ?? [];
        foreach (['procedure_id', 'title', 'step', 'kind'] as $field) {
            if (!isset($metadata[$field]) || trim((string) $metadata[$field]) === '') {
                throw new InvalidArgumentException("Runbook result $index requires metadata.$field");
            }
        }
        if (filter_var($metadata['step'], FILTER_VALIDATE_INT) === false
            || (int) $metadata['step'] < 0) {
            throw new InvalidArgumentException("Runbook result $index requires a non-negative step");
        }

        $kind = (string) $metadata['kind'];
        if (!in_array($kind, $allowed_kinds, true)) {
            throw new InvalidArgumentException("Runbook result $index has an unsupported kind");
        }

        $metadata['step'] = (int) $metadata['step'];
        $normalized[] = array_merge($result, ['metadata' => $metadata]);
    }

    usort($normalized, function ($left, $right) {
        $left_key = [$left['metadata']['procedure_id'], $left['metadata']['step']];
        $right_key = [$right['metadata']['procedure_id'], $right['metadata']['step']];
        return $left_key <=> $right_key;
    });

    $bundle = evidence_build_bundle($normalized);
    $lines = [];
    foreach ($normalized as $index => $result) {
        $metadata = $result['metadata'];
        $label = (string) ($index + 1);
        $lines[] = sprintf(
            '[%s] %s | %s | step %d | %s' . "\n" . '%s',
            $label,
            $metadata['procedure_id'],
            $metadata['title'],
            $metadata['step'],
            strtoupper($metadata['kind']),
            trim((string) $result['text'])
        );
    }

    $bundle['context'] = implode("\n\n", $lines);
    $bundle['procedure_ids'] = array_values(array_unique(array_map(function ($result) {
        return $result['metadata']['procedure_id'];
    }, $normalized)));
    return $bundle;
}
