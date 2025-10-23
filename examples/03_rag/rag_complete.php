<?php
/**
 * Test RAG Completo - End to End
 * Ingestion → Retrieval → Prompt → (pronto per LLM)
 */

require_once __DIR__ . '/../datapizza/pipeline/ingestion_pipeline.php';
require_once __DIR__ . '/../datapizza/modules/retrieval_utils.php';
require_once __DIR__ . '/../datapizza/modules/prompt/chat_prompt_template.php';
require_once __DIR__ . '/../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../datapizza/vectorstores/simple_vectorstore.php';

if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - RAG Pipeline Completo          ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Setup
$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore(__DIR__ . '/../data/rag_complete.json');

// FASE 1: Ingestion (se DB vuoto)
$doc_count = $vectorstore->count();
if ($doc_count == 0) {
    echo "📚 Fase 1: Ingestion documenti...\n";
    echo str_repeat("═", 54) . "\n\n";
    
    $docs = array(
        "DataPizza-AI-PHP è un framework per sviluppare applicazioni AI in PHP puro. Supporta RAG, embeddings, agenti ReAct e tools vari.",
        "Il framework include 6 provider LLM: OpenAI, DeepSeek, Claude, Gemini, Mistral e Kimi. Tutti compatibili API OpenAI.",
        "Tools disponibili: Calculator per matematica, DateTime per date, Wikipedia per ricerche, FileReader per file, DuckDuckGo per web search.",
        "Architettura modulare con pipeline ingestion, prompt templates, conversation memory, vector search e integration n8n/MCP."
    );
    
    pipeline_ingest($docs, $embedder, $vectorstore, 200, 30, array('source' => 'docs'));
    $doc_count = $vectorstore->count();
}

echo "✓ Database: $doc_count documenti\n\n";

// FASE 2: Query utente
echo "💬 Fase 2: Query utente\n";
echo str_repeat("═", 54) . "\n";
$user_query = "Quali tools sono disponibili nel framework?";
echo "User: $user_query\n\n";

// FASE 3: Retrieval
echo "🔍 Fase 3: Retrieval documenti rilevanti...\n";
$results = retrieval_search($vectorstore, $embedder, $user_query, 2, 0.0);
echo "✓ Trovati: " . count($results) . " documenti\n\n";

foreach ($results as $idx => $r) {
    echo "  Doc " . ($idx + 1) . ": " . substr($r['text'], 0, 50) . "...\n";
}

// FASE 4: Format context
echo "\n📝 Fase 4: Format context per LLM...\n";
$context = retrieval_format_context($results);
echo "✓ Context: " . strlen($context) . " caratteri\n\n";

// FASE 5: Generate prompt
echo "🎯 Fase 5: Generate prompt finale...\n";
$final_prompt = prompt_rag($context, $user_query);
echo "✓ Prompt generato\n\n";

echo str_repeat("═", 54) . "\n";
echo "📄 PROMPT FINALE DA INVIARE A LLM:\n";
echo str_repeat("═", 54) . "\n";
echo $final_prompt . "\n";
echo str_repeat("═", 54) . "\n\n";

echo "✅ Pipeline RAG completa!\n";
echo "\n💡 Questo prompt è ora pronto per essere inviato a:\n";
echo "   - OpenAI (gpt-4o-mini)\n";
echo "   - DeepSeek\n";
echo "   - Claude\n";
echo "   - Qualsiasi LLM compatibile!\n";
