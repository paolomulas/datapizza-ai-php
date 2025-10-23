<?php
/**
 * Test DAG Pipeline - Esempio Educational
 */

require_once __DIR__ . '/../datapizza/pipeline/dag_pipeline.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - DAG Pipeline Demo              ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "📝 Esempio: Pipeline testo → uppercase → reverse → count\n\n";

// Crea pipeline
$pipeline = dag_create();

// Modulo 1: Uppercase
$pipeline = dag_add_module($pipeline, 'uppercase', function($input) {
    return strtoupper($input);
});

// Modulo 2: Reverse
$pipeline = dag_add_module($pipeline, 'reverse', function($input) {
    return strrev($input);
});

// Modulo 3: Count caratteri
$pipeline = dag_add_module($pipeline, 'count', function($input) {
    return "Testo: '$input' | Lunghezza: " . strlen($input);
});

// Connetti moduli in sequenza
$pipeline = dag_connect($pipeline, 'uppercase', 'reverse');
$pipeline = dag_connect($pipeline, 'reverse', 'count');

// Esegui pipeline
$input_text = "Hello DataPizza";
echo "Input: '$input_text'\n\n";

$result = dag_run($pipeline, 'uppercase', $input_text);

echo "Risultato finale:\n$result\n\n";

// Esempio 2: Pipeline RAG semplificata
echo str_repeat("═", 54) . "\n";
echo "📝 Esempio 2: Pipeline RAG simulata\n\n";

$rag_pipeline = dag_create();

// Simula moduli RAG
$rag_pipeline = dag_add_module($rag_pipeline, 'query', function($input) {
    return "Query: " . $input;
});

$rag_pipeline = dag_add_module($rag_pipeline, 'embed', function($input) {
    return "$input → [0.234, -0.891, ...] (embedding)";
});

$rag_pipeline = dag_add_module($rag_pipeline, 'search', function($input) {
    return "$input → Found 2 docs";
});

$rag_pipeline = dag_add_module($rag_pipeline, 'prompt', function($input) {
    return "$input → Formatted prompt";
});

// Connetti in catena
$rag_pipeline = dag_connect($rag_pipeline, 'query', 'embed');
$rag_pipeline = dag_connect($rag_pipeline, 'embed', 'search');
$rag_pipeline = dag_connect($rag_pipeline, 'search', 'prompt');

$user_query = "Come funziona il RAG?";
echo "Query: '$user_query'\n\n";

$final = dag_run($rag_pipeline, 'query', $user_query);

echo "✅ Test completati!\n";
echo "\n💡 La DAG Pipeline permette di comporre moduli\n";
echo "   in sequenze personalizzate, come LEGO!\n";
