<?php
/**
 * DAG Pipeline RAG - CON MODULI REALI
 */

require_once __DIR__ . '/../datapizza/pipeline/dag_pipeline.php';
require_once __DIR__ . '/../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../datapizza/vectorstores/simple_vectorstore.php';
require_once __DIR__ . '/../datapizza/modules/retrieval_utils.php';
require_once __DIR__ . '/../datapizza/modules/prompt/chat_prompt_template.php';

if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DAG Pipeline RAG - MODULI REALI             ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Setup componenti (da dove vengono i dati REALI!)
$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore(__DIR__ . '/../data/rag_complete.json');

echo "📊 VectorStore: " . $vectorstore->count() . " documenti\n\n";

// Crea pipeline con MODULI REALI
$pipeline = dag_create();

// MODULO 1: Embedding (API OpenAI!)
$pipeline = dag_add_module($pipeline, 'embed', function($query) use ($embedder) {
    echo "  → Chiamata API OpenAI per embedding...\n";
    return array(
        'query' => $query,
        'embedding' => $embedder->embed($query)  // ← API CALL REALE!
    );
});

// MODULO 2: Retrieval (Vector search reale!)
$pipeline = dag_add_module($pipeline, 'retrieve', function($data) use ($vectorstore) {
    echo "  → Ricerca nel vectorstore...\n";
    $results = $vectorstore->search($data['embedding'], 2);  // ← SEARCH REALE!
    return array(
        'query' => $data['query'],
        'documents' => $results
    );
});

// MODULO 3: Format context
$pipeline = dag_add_module($pipeline, 'format', function($data) {
    echo "  → Formattazione context...\n";
    $context = retrieval_format_context($data['documents']);  // ← FORMATTING REALE!
    return array(
        'query' => $data['query'],
        'context' => $context
    );
});

// MODULO 4: Generate prompt
$pipeline = dag_add_module($pipeline, 'prompt', function($data) {
    echo "  → Generazione prompt...\n";
    $prompt = prompt_rag($data['context'], $data['query']);  // ← TEMPLATE REALE!
    return $prompt;
});

// Connetti moduli in sequenza
$pipeline = dag_connect($pipeline, 'embed', 'retrieve');
$pipeline = dag_connect($pipeline, 'retrieve', 'format');
$pipeline = dag_connect($pipeline, 'format', 'prompt');

// ESEGUI con query REALE da user!
$user_query = "Quali tools sono disponibili?";
echo "💬 User query: '$user_query'\n\n";

$final_prompt = dag_run($pipeline, 'embed', $user_query);

echo "📄 PROMPT FINALE (pronto per LLM):\n";
echo str_repeat("═", 54) . "\n";
echo $final_prompt . "\n";
echo str_repeat("═", 54) . "\n\n";

echo "✅ Pipeline RAG completa con moduli REALI!\n";
echo "\n💡 Ogni modulo ha fatto lavoro VERO:\n";
echo "   - Embed: API call OpenAI\n";
echo "   - Retrieve: Vector search cosine\n";
echo "   - Format: String processing\n";
echo "   - Prompt: Template substitution\n";
