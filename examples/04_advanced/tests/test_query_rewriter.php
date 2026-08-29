<?php
/**
 * Test Query Rewriter
 */

require_once __DIR__ . '/../../../datapizza/modules/rewriters/query_rewriter.php';

if (file_exists(__DIR__ . '/../../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - Query Rewriter Demo            ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Test 1: Optimize query
echo "🔍 Test 1: Query Optimization (local rules)\n";
echo str_repeat("═", 54) . "\n";

$queries = array(
    "Come funziona questa cosa qui?",
    "Voglio sapere info sul framework",
    "Quali sono gli strumenti disponibili?"
);

foreach ($queries as $q) {
    echo "Original: '$q'\n";
    $optimized = rewriter_optimize_query($q);
    echo "Optimized: '$optimized'\n\n";
}

// Test 2: Keyword extraction (locale)
echo str_repeat("═", 54) . "\n";
echo "🔍 Test 2: Keyword Extraction (locale)\n";
echo str_repeat("═", 54) . "\n";

$test_query = "Come posso usare il framework DataPizza per creare un chatbot RAG?";
echo "Query: '$test_query'\n";
$keywords = rewriter_extract_keywords($test_query);
echo "Keywords: " . implode(', ', $keywords) . "\n\n";

// Test 3: Query expansion (local dictionary)
echo str_repeat("═", 54) . "\n";
echo "🔍 Test 3: Query Expansion (local dictionary)\n";
echo str_repeat("═", 54) . "\n";

$expand_query = "install api error";
echo "Query: '$expand_query'\n";
$expanded = rewriter_expand_query(rewriter_extract_keywords($expand_query));
echo "Expansions: $expanded\n";

if (strpos($expanded, 'setup') === false || strpos($expanded, 'endpoint') === false) {
    fwrite(STDERR, "❌ Expected local synonym expansion was not produced.\n");
    exit(1);
}

echo "\n✅ Test completati!\n";
echo "\nIl test verifica soltanto le regole locali; non misura la qualità del retrieval.\n";
