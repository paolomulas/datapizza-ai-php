<?php
/**
 * 🍕 DAG Pipeline - Building AI Workflows Like LEGO
 * 
 * Welcome to advanced territory.
 * 
 * So far, you've used fixed pipelines: ingestion → retrieval → prompt → LLM
 * But what if you want to CUSTOMIZE the flow?
 * 
 * Enter: DAG (Directed Acyclic Graph) Pipelines
 * 
 * Think of it as LEGO for AI workflows:
 * - Each module is a LEGO brick (a function that transforms data)
 * - You connect bricks in any order you want
 * - Data flows through the graph from module to module
 * - "Acyclic" = no loops, just forward flow
 * 
 * Why this matters:
 * - Experiment with different processing orders
 * - Add/remove modules easily
 * - Build complex workflows visually (in your mind or on paper)
 * - Reuse modules across different pipelines
 * 
 * This is how enterprise AI platforms work (Airflow, n8n, LangChain).
 * Now you're building it yourself. In PHP. 🧩
 */

require_once __DIR__ . '/../datapizza/pipeline/dag_pipeline.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - DAG Pipeline Demo              ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// ========================================
// EXAMPLE 1: Simple text processing pipeline
// ========================================
echo "📝 Example 1: Text → UPPERCASE → Reverse → Count\n\n";

// Create empty pipeline
$pipeline = dag_create();

// Module 1: Convert to uppercase
// Each module is just a function that takes input and returns output
$pipeline = dag_add_module($pipeline, 'uppercase', function($input) {
    return strtoupper($input);
});

// Module 2: Reverse the string
$pipeline = dag_add_module($pipeline, 'reverse', function($input) {
    return strrev($input);
});

// Module 3: Count characters and format output
$pipeline = dag_add_module($pipeline, 'count', function($input) {
    return "Text: '$input' | Length: " . strlen($input);
});

// Connect modules in sequence (define the flow)
// uppercase → reverse → count
$pipeline = dag_connect($pipeline, 'uppercase', 'reverse');
$pipeline = dag_connect($pipeline, 'reverse', 'count');

// Run the pipeline
$input_text = "Hello DataPizza";
echo "Input: '$input_text'\n\n";

// Execute: data flows through uppercase → reverse → count
$result = dag_run($pipeline, 'uppercase', $input_text);

echo "Final result:\n$result\n\n";

// ========================================
// EXAMPLE 2: Simulated RAG pipeline
// ========================================
echo str_repeat("═", 54) . "\n";
echo "📝 Example 2: Simulated RAG pipeline\n\n";

$rag_pipeline = dag_create();

// Simulate RAG modules (simplified for demo)
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

// Connect in chain: query → embed → search → prompt
$rag_pipeline = dag_connect($rag_pipeline, 'query', 'embed');
$rag_pipeline = dag_connect($rag_pipeline, 'embed', 'search');
$rag_pipeline = dag_connect($rag_pipeline, 'search', 'prompt');

$user_query = "How does RAG work?";
echo "Query: '$user_query'\n\n";

$final = dag_run($rag_pipeline, 'query', $user_query);

echo "Pipeline trace:\n$final\n\n";

echo "✅ Tests completed!\n";
echo "\n💡 DAG Pipeline lets you compose modules\n";
echo "   in custom sequences, like LEGO blocks!\n";

/**
 * 🎓 Understanding DAG Pipelines:
 * 
 * WHAT IS A DAG?
 * ─────────────
 * DAG = Directed Acyclic Graph
 * 
 * - DIRECTED: Edges have direction (data flows one way)
 * - ACYCLIC: No loops (no going back)
 * - GRAPH: Nodes (modules) connected by edges (connections)
 * 
 * Visual representation of Example 1:
 * 
 *   "Hello DataPizza"
 *          ↓
 *    [uppercase]
 *          ↓
 *    "HELLO DATAPIZZA"
 *          ↓
 *     [reverse]
 *          ↓
 *    "AZZIPATAD OLLEH"
 *          ↓
 *      [count]
 *          ↓
 *    "Text: '...' | Length: 17"
 * 
 * CORE OPERATIONS:
 * ───────────────
 * 1. dag_create() → Initialize empty pipeline
 * 2. dag_add_module(name, function) → Add processing node
 * 3. dag_connect(from, to) → Define data flow
 * 4. dag_run(start_module, input) → Execute the pipeline
 * 
 * WHY USE DAG PIPELINES?
 * ─────────────────────
 * 
 * ✅ Modularity
 *    Each module does ONE thing well
 *    Easy to test, debug, replace
 * 
 * ✅ Flexibility
 *    Rearrange modules without rewriting code
 *    Example: embed → search → rerank → prompt
 *    vs: embed → rerank → search → prompt
 * 
 * ✅ Reusability
 *    Same module in different pipelines
 *    "embed" module used in ingestion AND retrieval
 * 
 * ✅ Visibility
 *    See the exact flow of data transformations
 *    Easier to understand complex systems
 * 
 * REAL-WORLD RAG PIPELINE:
 * ───────────────────────
 * In Example 2, we simulated a RAG flow:
 * 
 * User Query → Embed → Search → Prompt → (LLM)
 * 
 * With DAG, you could easily add modules:
 * - Query → [Query Rewrite] → Embed → ...
 * - ... → Search → [Reranker] → Prompt → ...
 * - ... → Prompt → [Cache Check] → LLM → ...
 * 
 * Each addition is just:
 * 1. dag_add_module('reranker', function($docs) {...})
 * 2. dag_connect('search', 'reranker')
 * 3. dag_connect('reranker', 'prompt')
 * 
 * COMPARISON TO OTHER TOOLS:
 * ─────────────────────────
 * This is the same concept as:
 * - Apache Airflow (Python data pipelines)
 * - n8n (no-code workflow automation)
 * - LangChain LCEL (LangChain Expression Language)
 * - Zapier/Make workflows
 * 
 * But you're seeing it implemented in ~100 lines of PHP.
 * No framework magic. Pure procedural logic.
 * 
 * ADVANTAGES OF YOUR IMPLEMENTATION:
 * ──────────────────────────────────
 * ✅ Transparent - Open dag_pipeline.php and read the code
 * ✅ Lightweight - Works on 2011 Raspberry Pi
 * ✅ No dependencies - Just PHP arrays and functions
 * ✅ Educational - You understand HOW it works, not just using it
 * 
 * NEXT STEPS:
 * ──────────
 * Try building your own pipeline:
 * - Add a "cache" module that checks if query was asked before
 * - Add a "filter" module that removes low-quality results
 * - Add a "summarize" module that condenses retrieved docs
 * 
 * Each is just a function. Each plugs into the graph.
 * That's the power of modular design.
 * 
 * You're not just using AI tools anymore.
 * You're ARCHITECTING AI systems. 🍕🧩
 */
?>
