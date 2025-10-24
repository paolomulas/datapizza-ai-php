<?php
/**
 * 🍕 Complete RAG Pipeline - End to End Breakdown
 * 
 * In the previous demo, you saw RAG working as a black box.
 * The agent handled everything automatically.
 * 
 * Now let's OPEN THE BOX and see each step:
 * 
 * 1️⃣ INGESTION → Convert documents to embeddings and store them
 * 2️⃣ RETRIEVAL → Search for relevant documents using semantic similarity
 * 3️⃣ FORMATTING → Prepare retrieved context for the LLM
 * 4️⃣ PROMPTING → Build the final prompt with context + question
 * 5️⃣ GENERATION → (You'll send this to any LLM)
 * 
 * This is RAG DECOMPOSED. Every step visible. Every transformation clear.
 * 
 * After this, you'll understand RAG at a deep level.
 * Not just "it works" - but "this is HOW it works". 🔍
 */

require_once __DIR__ . '/../../datapizza/pipeline/ingestion_pipeline.php';
require_once __DIR__ . '/../../datapizza/modules/retrieval_utils.php';
require_once __DIR__ . '/../../datapizza/modules/prompt/chat_prompt_template.php';
require_once __DIR__ . '/../../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../../datapizza/vectorstores/simple_vectorstore.php';

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - Complete RAG Pipeline          ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Setup components
$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore(__DIR__ . '/../../data/rag_complete.json');

// ========================================
// PHASE 1: INGESTION (if DB empty)
// ========================================
$doc_count = $vectorstore->count();
if ($doc_count == 0) {
    echo "📚 Phase 1: Document Ingestion...\n";
    echo str_repeat("═", 54) . "\n\n";
    
    // Your knowledge base documents
    $docs = array(
        "DataPizza-AI-PHP is a framework for building AI applications in pure PHP. It supports RAG, embeddings, ReAct agents and various tools.",
        "The framework includes 6 LLM providers: OpenAI, DeepSeek, Claude, Gemini, Mistral and Kimi. All OpenAI API compatible.",
        "Available tools: Calculator for math, DateTime for dates, Wikipedia for searches, FileReader for files, DuckDuckGo for web search.",
        "Modular architecture with ingestion pipeline, prompt templates, conversation memory, vector search and n8n/MCP integration."
    );
    
    // Ingest: convert docs → embeddings → store
  pipeline_ingest_texts($docs, $embedder, $vectorstore, 200, 30, array('source' => 'docs'));

    $doc_count = $vectorstore->count();
}

echo "✓ Database: $doc_count documents\n\n";

// ========================================
// PHASE 2: USER QUERY
// ========================================
echo "💬 Phase 2: User Query\n";
echo str_repeat("═", 54) . "\n";
$user_query = "What tools are available in the framework?";
echo "User: $user_query\n\n";

// ========================================
// PHASE 3: RETRIEVAL
// ========================================
echo "🔍 Phase 3: Retrieving relevant documents...\n";
// This converts the query to embedding and finds similar docs
$results = retrieval_search($embedder, $vectorstore, $user_query, 2, 0.0);


echo "✓ Found: " . count($results) . " documents\n\n";

foreach ($results as $idx => $r) {
    echo "  Doc " . ($idx + 1) . ": " . substr($r['text'], 0, 50) . "...\n";
}

// ========================================
// PHASE 4: FORMAT CONTEXT
// ========================================
echo "\n📝 Phase 4: Formatting context for LLM...\n";
// Combine retrieved documents into a single context string
$context = retrieval_format_context($results);
echo "✓ Context: " . strlen($context) . " characters\n\n";

// ========================================
// PHASE 5: GENERATE PROMPT
// ========================================
echo "🎯 Phase 5: Generating final prompt...\n";
// Build the complete prompt: system message + context + user question
$final_prompt = prompt_rag($context, $user_query);
echo "✓ Prompt generated\n\n";

echo str_repeat("═", 54) . "\n";
echo "📄 FINAL PROMPT TO SEND TO LLM:\n";
echo str_repeat("═", 54) . "\n";
echo $final_prompt . "\n";
echo str_repeat("═", 54) . "\n\n";

echo "✅ Complete RAG pipeline!\n";
echo "\n💡 This prompt is now ready to be sent to:\n";
echo "   - OpenAI (gpt-4o-mini)\n";
echo "   - DeepSeek\n";
echo "   - Claude\n";
echo "   - Any compatible LLM!\n";

/**
 * 🎓 What you just witnessed - The RAG Pipeline Explained:
 * 
 * PHASE 1: INGESTION (One-time setup)
 * ────────────────────────────────────
 * Documents → Text Chunking → Embeddings → Vector Store
 * 
 * What happened:
 * - Took 4 documents about DataPizza
 * - For each: called embedder.embed() → got 1536-dim vector
 * - Stored: {text, embedding, metadata} in vectorstore
 * - Now searchable by semantic similarity
 * 
 * PHASE 2: USER QUERY
 * ───────────────────
 * Simply: "What tools are available in the framework?"
 * 
 * PHASE 3: RETRIEVAL (The Magic)
 * ──────────────────────────────
 * Query → Embedding → Vector Search → Top-K Results
 * 
 * What happened:
 * 1. Converted query to embedding (same 1536-dim space)
 * 2. Calculated cosine similarity with ALL stored embeddings
 * 3. Returned top 2 most similar documents
 * 
 * Why this works:
 * - Documents about "tools" have embeddings close to query about "tools"
 * - "Closeness" in vector space = semantic similarity
 * - No keyword matching needed - understands MEANING
 * 
 * PHASE 4: FORMAT CONTEXT
 * ───────────────────────
 * Combined retrieved docs into single context string
 * This becomes the "knowledge" the LLM will use
 * 
 * PHASE 5: GENERATE PROMPT
 * ────────────────────────
 * Built final prompt structure:
 * 
 * System: "You are a helpful assistant. Use the context below..."
 * Context: [Retrieved documents]
 * User: [Original question]
 * 
 * This prompt is what gets sent to the LLM.
 * The LLM sees the context and can answer accurately.
 * 
 * ═══════════════════════════════════════════════════════
 * 
 * THE COMPLETE RAG FLOW:
 * 
 * Offline (Setup):
 *   Documents → Embeddings → Vector Store
 * 
 * Online (Each Query):
 *   Query → Embedding → Search → Retrieve → Format → Prompt → LLM → Answer
 * 
 * ═══════════════════════════════════════════════════════
 * 
 * Why this is powerful:
 * 
 * ✅ Grounded answers - LLM uses YOUR documents, not memorized data
 * ✅ Always current - update knowledge base anytime
 * ✅ Transparent - you see exactly what context the LLM receives
 * ✅ Efficient - only relevant docs are retrieved, not entire database
 * ✅ Scalable - works with 10 docs or 10,000 docs
 * 
 * This is production RAG architecture.
 * This is how enterprise AI assistants work.
 * This is how you prevent hallucinations.
 * 
 * And you just built it. Step by step. In PHP. On a 2011 board.
 * With complete visibility into every transformation.
 * 
 * Now you don't just USE RAG - you UNDERSTAND RAG. 🍕🚀
 */
?>
