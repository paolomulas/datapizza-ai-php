<?php
/**
 * 🍕 Ingestion Pipeline - Building Your Knowledge Base
 * 
 * Before RAG can answer questions, it needs KNOWLEDGE to search through.
 * That's what ingestion is: loading documents into the vector store.
 * 
 * Think of it like building a library:
 * 1. You have books (documents)
 * 2. You create index cards for each book (embeddings)
 * 3. You organize cards in a filing system (vector store)
 * 4. Later, you can quickly find relevant books (retrieval)
 * 
 * This is the "offline" phase of RAG:
 * - Run once (or periodically when docs change)
 * - Takes time (API calls, processing)
 * - Creates the searchable knowledge base
 * 
 * The "online" phase (retrieval) is instant because ingestion did the work.
 * 
 * In this example:
 * - 3 documents about DataPizza framework
 * - Each converted to embeddings
 * - Stored with metadata
 * - Verified with semantic search
 * 
 * This is how you prepare data for production RAG systems. 📚
 */

require_once __DIR__ . '/../datapizza/pipeline/ingestion_pipeline.php';
require_once __DIR__ . '/../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../datapizza/vectorstores/simple_vectorstore.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - Ingestion Pipeline Demo        ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// ========================================
// STEP 1: Initialize components
// ========================================
echo "📦 Initializing components...\n";
$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore(__DIR__ . '/../data/test_ingestion.json');
$vectorstore->clear_all(); // Start fresh for demo
echo " ✓ Embedder and VectorStore ready\n\n";

// ========================================
// STEP 2: Prepare documents to ingest
// ========================================
// These are the documents you want to make searchable
$documents = array(
    "DataPizza-AI-PHP is a framework for building AI applications in PHP. It supports embeddings, vector search, RAG and ReAct agents.",
    
    "The framework includes clients for OpenAI, DeepSeek, Claude, Gemini, Mistral and Kimi. All providers are compatible with OpenAI API.",
    
    "The architecture is modular with tools like Calculator, DateTime, Wikipedia, conversation memory and n8n/MCP integration for orchestration."
);

// ========================================
// STEP 3: Run ingestion pipeline
// ========================================
// Parameters:
// - documents: array of text strings
// - embedder: converts text to vectors
// - vectorstore: where to save
// - chunk_size: max characters per chunk (300)
// - overlap: characters overlap between chunks (50)
// - metadata: custom data to attach to each document
echo "🔄 Running ingestion pipeline...\n\n";

$stats = pipeline_ingest(
    $documents,
    $embedder,
    $vectorstore,
    300,    // chunk_size: split if document > 300 chars
    50,     // overlap: 50 chars overlap between chunks
    array('source' => 'framework_docs', 'version' => '1.0')  // metadata
);

// ========================================
// STEP 4: Verify data was saved
// ========================================
echo "🔍 Verifying saved data...\n";
$doc_count = $vectorstore->count();
echo " ✓ Documents in vectorstore: " . $doc_count . "\n";

// ========================================
// STEP 5: Test semantic search
// ========================================
echo "\n🔎 Testing semantic search...\n";
$query_text = "How does the framework work?";
$query_emb = $embedder->embed($query_text);
$results = $vectorstore->search($query_emb, 2);

echo "Query: '$query_text'\n";
echo "Results found: " . count($results) . "\n\n";

foreach ($results as $idx => $result) {
    echo "Result " . ($idx + 1) . ":\n";
    echo "  Similarity: " . round($result['similarity'], 4) . "\n";
    echo "  Text: " . substr($result['text'], 0, 80) . "...\n\n";
}

echo "✅ Test completed!\n";
echo "\n💡 Check the file: data/test_ingestion.json\n";
echo "   You'll see all documents with their embeddings!\n";

/**
 * 🎓 Understanding the Ingestion Pipeline:
 * 
 * WHAT HAPPENED BEHIND THE SCENES:
 * ════════════════════════════════
 * 
 * For each document, the pipeline:
 * 
 * 1. TEXT CHUNKING
 *    - Checks if document > 300 characters (chunk_size)
 *    - If yes, splits into smaller chunks with 50-char overlap
 *    - Why overlap? So chunks share context at boundaries
 *    - Example: "...end of chunk1" overlaps with "end of chunk1..."
 * 
 * 2. EMBEDDING GENERATION
 *    - Calls OpenAI API for each chunk
 *    - text-embedding-3-small model
 *    - Receives 1536-dimensional vector
 *    - This is the "semantic fingerprint" of the text
 * 
 * 3. METADATA ATTACHMENT
 *    - Adds custom metadata to each chunk
 *    - In this case: {source: 'framework_docs', version: '1.0'}
 *    - Useful for filtering, tracking, versioning
 * 
 * 4. STORAGE
 *    - Saves to vectorstore.json
 *    - Format: {id, text, embedding, metadata, timestamp}
 *    - Now searchable by semantic similarity
 * 
 * 
 * WHY CHUNKING MATTERS:
 * ════════════════════
 * 
 * Problem: Long documents
 * - Embeddings lose precision with very long text
 * - LLMs have context limits (can't process huge docs)
 * - Need to focus on relevant PARTS, not entire document
 * 
 * Solution: Split into chunks
 * - Each chunk is semantically coherent
 * - Small enough for accurate embeddings
 * - Can retrieve only relevant sections
 * 
 * Example:
 * Document (1000 chars) → 4 chunks (250 chars each)
 * Query: "How to use Calculator tool?"
 * → Retrieves only chunk 3 (about tools)
 * → LLM sees focused context, not entire doc
 * 
 * 
 * WHY OVERLAP MATTERS:
 * ═══════════════════
 * 
 * Without overlap:
 * Chunk 1: "...the framework supports tools like Calculator"
 * Chunk 2: "for math operations, DateTime for dates..."
 * 
 * Problem: Context split! "Calculator" separated from "math operations"
 * 
 * With 50-char overlap:
 * Chunk 1: "...framework supports tools like Calculator for math"
 * Chunk 2: "Calculator for math operations, DateTime for dates..."
 * 
 * Benefit: Both chunks mention Calculator + its purpose
 * Better chance of correct retrieval
 * 
 * 
 * INGESTION VS RETRIEVAL:
 * ══════════════════════
 * 
 * INGESTION (this script):
 * - Runs ONCE (or when docs change)
 * - SLOW (API calls for each document)
 * - Prepares knowledge base
 * - Offline operation
 * 
 * RETRIEVAL (search):
 * - Runs EVERY query
 * - FAST (just vector math)
 * - Searches prepared knowledge base
 * - Online operation
 * 
 * Analogy:
 * - Ingestion = Building an index at the back of a book (slow)
 * - Retrieval = Looking up a word in the index (fast)
 * 
 * 
 * METADATA USE CASES:
 * ══════════════════
 * 
 * You can filter by metadata during search:
 * 
 * metadata: {
 *   source: 'user_manual',
 *   version: '2.0',
 *   language: 'en',
 *   category: 'api_docs',
 *   last_updated: '2025-10-23'
 * }
 * 
 * Then search only:
 * - Documents from 'user_manual'
 * - Version 2.0 or higher
 * - English language
 * - Updated after Oct 1st
 * 
 * This makes RAG systems much more powerful!
 * 
 * 
 * PARAMETERS EXPLAINED:
 * ════════════════════
 * 
 * chunk_size: 300
 * - Max 300 characters per chunk
 * - Adjust based on your use case:
 *   • Technical docs: 200-400 (focused)
 *   • Articles: 500-1000 (more context)
 *   • Code snippets: 100-300 (granular)
 * 
 * overlap: 50
 * - 50 characters overlap between chunks
 * - Typical: 10-20% of chunk_size
 * - More overlap = more redundancy but better context
 * - Less overlap = more unique chunks but risk losing context
 * 
 * 
 * COST CONSIDERATIONS:
 * ═══════════════════
 * 
 * Ingestion costs:
 * - text-embedding-3-small: $0.02 per 1M tokens
 * - Typical document: ~200 tokens
 * - 1000 documents ≈ 200,000 tokens ≈ $0.004
 * 
 * Very cheap! But adds up with millions of docs.
 * 
 * Pro tip: Cache embeddings!
 * - Same document = same embedding
 * - Store hash of text, check before embedding
 * - Only re-embed if text changed
 * 
 * 
 * PRODUCTION CONSIDERATIONS:
 * ═════════════════════════
 * 
 * For real systems, add:
 * ✅ Batch processing (embed multiple docs per API call)
 * ✅ Error handling (retry failed embeddings)
 * ✅ Progress tracking (show % completed)
 * ✅ Duplicate detection (skip already-ingested docs)
 * ✅ Versioning (track when docs were ingested)
 * ✅ Incremental updates (only ingest new/changed docs)
 * 
 * But the CORE is what you just saw:
 * Documents → Chunks → Embeddings → Storage
 * 
 * This is the foundation of every RAG system.
 * From tiny projects to enterprise knowledge bases.
 * 
 * You've just built the data preparation layer. 🍕📚
 */
?>
