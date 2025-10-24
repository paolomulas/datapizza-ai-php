<?php
/**
 * 🍕 DAG Pipeline RAG - WITH REAL MODULES
 * 
 * In the previous example, you learned HOW DAG pipelines work
 * with simple text transformations (uppercase, reverse, count).
 * 
 * Now it's time to level up: REAL PRODUCTION MODULES.
 * 
 * This pipeline uses:
 * - REAL OpenAI API calls for embeddings
 * - REAL vector search with cosine similarity
 * - REAL document formatting
 * - REAL prompt template generation
 * 
 * Same DAG structure. Real operations. Production-ready.
 * 
 * What you're about to see:
 * - Each module does ACTUAL work (API calls, computations)
 * - Data flows through the graph, transformed at each step
 * - The final output is a prompt ready for any LLM
 * 
 * This is how you build REAL AI systems with DAG architecture.
 * Not toy examples. The real deal. 🚀
 */

require_once __DIR__ . '/../../datapizza/pipeline/dag_pipeline.php';
require_once __DIR__ . '/../../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../../datapizza/vectorstores/simple_vectorstore.php';
require_once __DIR__ . '/../../datapizza/modules/retrieval_utils.php';
require_once __DIR__ . '/../../datapizza/modules/prompt/chat_prompt_template.php';

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DAG Pipeline RAG - REAL MODULES             ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Setup real components (where the REAL data comes from!)
$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore(__DIR__ . '/../../data/rag_complete.json');

echo "📊 VectorStore: " . $vectorstore->count() . " documents\n\n";

// Create pipeline with REAL MODULES
$pipeline = dag_create();

// ========================================
// MODULE 1: Embedding (Real OpenAI API!)
// ========================================
$pipeline = dag_add_module($pipeline, 'embed', function($query) use ($embedder) {
    echo "  → Calling OpenAI API for embedding...\n";
    return array(
        'query' => $query,
        'embedding' => $embedder->embed($query)  // ← REAL API CALL!
    );
});

// ========================================
// MODULE 2: Retrieval (Real vector search!)
// ========================================
$pipeline = dag_add_module($pipeline, 'retrieve', function($data) use ($vectorstore) {
    echo "  → Searching vectorstore...\n";
    $results = $vectorstore->search($data['embedding'], 2);  // ← REAL SEARCH!
    return array(
        'query' => $data['query'],
        'documents' => $results
    );
});

// ========================================
// MODULE 3: Format context
// ========================================
$pipeline = dag_add_module($pipeline, 'format', function($data) {
    echo "  → Formatting context...\n";
    $context = retrieval_format_context($data['documents']);  // ← REAL FORMATTING!
    return array(
        'query' => $data['query'],
        'context' => $context
    );
});

// ========================================
// MODULE 4: Generate prompt
// ========================================
$pipeline = dag_add_module($pipeline, 'prompt', function($data) {
    echo "  → Generating prompt...\n";
    $prompt = prompt_rag($data['context'], $data['query']);  // ← REAL TEMPLATE!
    return $prompt;
});

// Connect modules in sequence
// embed → retrieve → format → prompt
$pipeline = dag_connect($pipeline, 'embed', 'retrieve');
$pipeline = dag_connect($pipeline, 'retrieve', 'format');
$pipeline = dag_connect($pipeline, 'format', 'prompt');

// EXECUTE with REAL user query!
$user_query = "What tools are available?";
echo "💬 User query: '$user_query'\n\n";

$final_prompt = dag_run($pipeline, 'embed', $user_query);

echo "📄 FINAL PROMPT (ready for LLM):\n";
echo str_repeat("═", 54) . "\n";
echo $final_prompt . "\n";
echo str_repeat("═", 54) . "\n\n";

echo "✅ Complete RAG pipeline with REAL modules!\n";
echo "\n💡 Each module did REAL work:\n";
echo "   - Embed: OpenAI API call\n";
echo "   - Retrieve: Cosine similarity vector search\n";
echo "   - Format: String processing\n";
echo "   - Prompt: Template substitution\n";

/**
 * 🎓 Understanding Real DAG Modules:
 * 
 * THE FLOW - Step by Step:
 * ════════════════════════
 * 
 * INPUT: "What tools are available?"
 *    ↓
 * [embed] MODULE:
 *    - Calls OpenAI API: text-embedding-3-small
 *    - Converts query to 1536-dimensional vector
 *    - Output: {query: "...", embedding: [0.234, -0.891, ...]}
 *    ↓
 * [retrieve] MODULE:
 *    - Takes embedding from previous module
 *    - Searches vectorstore using cosine similarity
 *    - Finds 2 most relevant documents
 *    - Output: {query: "...", documents: [{text: "...", score: 0.87}, ...]}
 *    ↓
 * [format] MODULE:
 *    - Takes documents array
 *    - Formats into single context string
 *    - Output: {query: "...", context: "Document 1: ...\nDocument 2: ..."}
 *    ↓
 * [prompt] MODULE:
 *    - Takes context + query
 *    - Applies RAG prompt template
 *    - Adds instructions: "Answer based on context..."
 *    - Output: Complete prompt string ready for LLM
 * 
 * FINAL OUTPUT: Prompt ready to send to gpt-4o-mini, DeepSeek, Claude, etc.
 * 
 * 
 * KEY DIFFERENCES FROM DEMO VERSION:
 * ══════════════════════════════════
 * 
 * Demo (previous example):
 * - Modules returned simple strings
 * - No external dependencies
 * - Simulated behavior
 * 
 * Real (this example):
 * ✅ Modules make HTTP API calls
 * ✅ Modules perform vector computations
 * ✅ Modules access file system (vectorstore.json)
 * ✅ Modules apply production templates
 * 
 * 
 * WHY USE DAG FOR RAG?
 * ═══════════════════
 * 
 * FLEXIBILITY:
 * Want to add a reranker? Just insert a module:
 * 
 * $pipeline = dag_add_module($pipeline, 'rerank', function($data) {
 *     // Sort results by custom scoring
 *     return $data;
 * });
 * dag_connect($pipeline, 'retrieve', 'rerank');
 * dag_connect($pipeline, 'rerank', 'format');
 * 
 * Want to cache embeddings? Add at the start:
 * 
 * $pipeline = dag_add_module($pipeline, 'cache_check', function($query) {
 *     // Check if embedding exists in cache
 *     return cached_embedding_or_compute($query);
 * });
 * dag_connect($pipeline, 'cache_check', 'retrieve');
 * 
 * DEBUGGING:
 * Each module prints what it's doing
 * You see exactly where the pipeline is at any moment
 * Easy to isolate issues: "Problem in retrieve module? Check vector search."
 * 
 * TESTING:
 * Test each module independently:
 * - Test embed: Does it return valid embedding?
 * - Test retrieve: Does it find correct docs?
 * - Test format: Is context well-structured?
 * 
 * REUSABILITY:
 * Use the same 'embed' module in:
 * - Ingestion pipeline (when adding docs)
 * - Retrieval pipeline (when searching)
 * - Any other pipeline that needs embeddings
 * 
 * 
 * COMPARISON TO MONOLITHIC APPROACH:
 * ═════════════════════════════════
 * 
 * Without DAG:
 * function rag_complete($query) {
 *     $emb = embed($query);
 *     $docs = search($emb);
 *     $ctx = format($docs);
 *     $prompt = build_prompt($ctx, $query);
 *     return $prompt;
 * }
 * 
 * Problems:
 * ❌ Hard to modify flow
 * ❌ Hard to test individual steps
 * ❌ Hard to reuse components
 * ❌ Hard to see what's happening
 * 
 * With DAG:
 * ✅ Add/remove/reorder modules easily
 * ✅ Test modules independently
 * ✅ Reuse modules across pipelines
 * ✅ Visual understanding of data flow
 * 
 * 
 * REAL-WORLD APPLICATIONS:
 * ═══════════════════════
 * 
 * This architecture is used in:
 * 
 * 🏢 Enterprise:
 *    - LangChain Expression Language (LCEL)
 *    - Haystack pipelines
 *    - LlamaIndex query engines
 * 
 * 🔧 DevOps:
 *    - Apache Airflow
 *    - Prefect
 *    - Dagster
 * 
 * 🎨 No-code:
 *    - n8n workflows
 *    - Zapier/Make chains
 *    - Bubble API workflows
 * 
 * 
 * YOU JUST BUILT IT:
 * ═════════════════
 * 
 * No framework.
 * No dependencies.
 * Just PHP arrays, functions, and closures.
 * 
 * And it works on a 2011 Raspberry Pi.
 * 
 * This is the power of understanding fundamentals.
 * You don't need heavy frameworks to build sophisticated systems.
 * You just need clear thinking and modular design.
 * 
 * Welcome to advanced AI engineering. 🍕🚀
 */
?>
