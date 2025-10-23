<?php
/**
 * Test Ingestion Pipeline
 */

require_once __DIR__ . '/../datapizza/pipeline/ingestion_pipeline.php';
require_once __DIR__ . '/../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../datapizza/vectorstores/simple_vectorstore.php';

if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - Ingestion Pipeline Demo        ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "📦 Inizializzazione componenti...\n";
$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore(__DIR__ . '/../data/test_ingestion.json');
$vectorstore->clear_all();
echo " ✓ Embedder e VectorStore pronti\n\n";

$documents = array(
    "DataPizza-AI-PHP è un framework per sviluppare applicazioni AI in PHP. Supporta embeddings, vector search, RAG e agenti ReAct.",
    
    "Il framework include client per OpenAI, DeepSeek, Claude, Gemini, Mistral e Kimi. Tutti i provider sono compatibili con API OpenAI.",
    
    "L'architettura è modulare con tools come Calculator, DateTime, Wikipedia, memory conversation e integration n8n/MCP per orchestrazione."
);

$stats = pipeline_ingest(
    $documents,
    $embedder,
    $vectorstore,
    300,
    50,
    array('source' => 'framework_docs', 'version' => '1.0')
);

echo "🔍 Verifica dati salvati...\n";
$doc_count = $vectorstore->count();
echo " ✓ Documenti nel vectorstore: " . $doc_count . "\n";

echo "\n🔎 Test semantic search...\n";
$query_text = "Come funziona il framework?";
$query_emb = $embedder->embed($query_text);
$results = $vectorstore->search($query_emb, 2);

echo "Query: '$query_text'\n";
echo "Risultati trovati: " . count($results) . "\n\n";

foreach ($results as $idx => $result) {
    echo "Risultato " . ($idx + 1) . ":\n";
    echo "  Similarity: " . round($result['similarity'], 4) . "\n";
    echo "  Text: " . substr($result['text'], 0, 80) . "...\n\n";
}

echo "✅ Test completato!\n";
