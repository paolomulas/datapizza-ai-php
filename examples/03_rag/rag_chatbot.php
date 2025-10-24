<?php
/**
 * 🍕 RAG Chatbot - The Complete Demo
 * 
 * This is where everything comes together.
 * 
 * You've learned:
 * - How to call LLMs (Example 1)
 * - How agents use tools (Example 2-4)
 * - How to add external knowledge (Wikipedia)
 * - How to maintain conversation memory
 * 
 * Now you're building a COMPLETE RAG SYSTEM:
 * - A knowledge base stored as embeddings
 * - Semantic search to find relevant information
 * - An agent that retrieves + reasons + generates answers
 * 
 * This is production-ready AI architecture.
 * This is how modern AI apps work.
 * And you're running it on a 2011 Raspberry Pi. 🚀
 */

require_once __DIR__ . '/../../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../../datapizza/vectorstores/simple_vectorstore.php';
require_once __DIR__ . '/../../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../../datapizza/tools/calculator.php';
require_once __DIR__ . '/../../datapizza/tools/datetime_tool.php';
require_once __DIR__ . '/../../datapizza/tools/rag_search.php';

// Load environment variables
$env = parse_ini_file(__DIR__ . '/../../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza RAG Chatbot - Complete Demo       ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// ========================================
// PHASE 1: Component initialization
// ========================================
echo "📦 Phase 1: Initializing components...\n";

$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore(__DIR__ . '/../../data/rag_demo.json');
$vectorstore->clear_all(); // Clean slate for demo

echo "   ✓ Embedder initialized\n";
echo "   ✓ Vector store initialized\n\n";

// ========================================
// PHASE 2: Loading knowledge base
// ========================================
echo "📚 Phase 2: Loading knowledge base...\n";

// This is your AI's "memory" - documents it can search through
$knowledge_base = [
    [
        'text' => 'DataPizza is a PHP framework for building AI applications. It includes clients for OpenAI and DeepSeek, support for embeddings, vector stores, tools, and agents with ReAct pattern.',
        'metadata' => ['source' => 'docs', 'topic' => 'overview']
    ],
    [
        'text' => 'The Raspberry Pi Model B Rev 2 has 512 MB RAM, ARM1176 single-core ARMv6 CPU at 700MHz, and was released in 2011. It is ideal for educational projects.',
        'metadata' => ['source' => 'docs', 'topic' => 'hardware']
    ],
    [
        'text' => 'The ReAct pattern (Reason + Act) allows AI agents to alternate between reasoning (Thought) and action (Action with tools). After each action, the agent receives an Observation and decides whether to continue or provide the Final Answer.',
        'metadata' => ['source' => 'docs', 'topic' => 'agents']
    ],
    [
        'text' => 'OpenAI text-embedding-3-small produces 1536-dimensional vectors and costs $0.02 per 1 million tokens. It is excellent for RAG applications.',
        'metadata' => ['source' => 'docs', 'topic' => 'embeddings']
    ],
    [
        'text' => 'The Simple Vector Store saves embeddings in JSON format on the filesystem. It is lightweight (works with 50 MB RAM) and perfect for prototypes or limited hardware like vintage Raspberry Pi.',
        'metadata' => ['source' => 'docs', 'topic' => 'vectorstores']
    ],
    [
        'text' => 'Tools in DataPizza extend agent capabilities. Calculator performs calculations, DateTime manipulates dates, FileReader reads files, and RAGSearch searches the knowledge base.',
        'metadata' => ['source' => 'docs', 'topic' => 'tools']
    ]
];

echo "   Loading " . count($knowledge_base) . " documents...\n";

// Convert each document to embeddings and store them
foreach ($knowledge_base as $doc) {
    // Step 1: Convert text to vector (embedding)
    $embedding = $embedder->embed($doc['text']);
    // Step 2: Store vector + text + metadata
    $vectorstore->add_document($doc['text'], $embedding, $doc['metadata']);
    echo "   ✓ Loaded: " . substr($doc['text'], 0, 50) . "...\n";
}

echo "   ✓ Knowledge base loaded (" . $vectorstore->count() . " documents)\n\n";

// ========================================
// PHASE 3: Creating RAG Agent
// ========================================
echo "🤖 Phase 3: Creating RAG Agent...\n";

// Equip the agent with tools - including RAG search!
$tools = [
    new RAGSearchTool($vectorstore, $embedder),   // 🔍 Main tool for RAG
    new Calculator(),
    new DateTimeTool()
];

// PHP 7.4: Use positional arguments
$agent = new ReactAgent(
    'openai',         // llm_provider
    'gpt-4o-mini',    // model
    $tools,           // tools
    5,                // max_iterations
    false             // verbose - Disabled for clean output
);

echo "   ✓ Agent configured with " . count($tools) . " tools\n\n";

// ========================================
// PHASE 4: Conversation demo
// ========================================
echo "💬 Phase 4: RAG Conversation Demo\n";
echo str_repeat("═", 54) . "\n\n";

// Test queries
$queries = [
    "What is DataPizza and what does it include?",
    "What are the specs of the Raspberry Pi Model B?",
    "Explain the ReAct pattern in simple terms",
    "How much does it cost to use OpenAI embeddings for 100,000 tokens?"
];

foreach ($queries as $i => $query) {
    echo "👤 User: $query\n";
    echo str_repeat("-", 54) . "\n";
    
    $start_time = microtime(true);
    $response = $agent->run($query);
    $elapsed = round((microtime(true) - $start_time) * 1000);
    
    echo "🤖 Assistant: $response\n";
    echo "   ⏱️  Response time: {$elapsed}ms\n\n";
    
    if ($i < count($queries) - 1) {
        echo str_repeat("─", 54) . "\n\n";
    }
}

// ========================================
// PHASE 5: Final statistics
// ========================================
echo str_repeat("═", 54) . "\n";
echo "📊 Final Statistics:\n";
echo "   • Documents in knowledge base: " . $vectorstore->count() . "\n";
echo "   • Available tools: " . count($tools) . "\n";
echo "   • Queries processed: " . count($queries) . "\n";
echo "\n✅ Demo completed!\n";
echo "\n💡 Tip: Try modifying the queries in demo_rag_chatbot.php\n";
echo "   to see how the agent responds to different questions!\n";

/**
 * 🎓 What you just built:
 * 
 * A COMPLETE RAG (Retrieval-Augmented Generation) SYSTEM.
 * 
 * Let's break down what happened:
 * 
 * PHASE 1-2: Knowledge Base Creation
 * - You took 6 documents about DataPizza
 * - Converted each to a 1536-dimensional vector (embedding)
 * - Stored them in a searchable vector store
 * 
 * PHASE 3: Agent Setup
 * - Created an agent with RAGSearch tool
 * - This tool can search the vector store semantically
 * - "Semantically" means by MEANING, not just keywords
 * 
 * PHASE 4: Magic Happens
 * For each question:
 * 1. Agent receives the query
 * 2. Recognizes it needs knowledge from the base
 * 3. Uses RAGSearch tool to find relevant documents
 * 4. Retrieves the most similar documents (by vector similarity)
 * 5. Reads the retrieved text
 * 6. Generates an answer using that context
 * 
 * This is RAG:
 * - RETRIEVE: Find relevant docs from vector store
 * - AUGMENT: Add them to the LLM prompt as context
 * - GENERATE: LLM creates answer based on retrieved facts
 * 
 * Why is this powerful?
 * - The LLM doesn't need to memorize facts (expensive training)
 * - You can update the knowledge base anytime (just add documents)
 * - It's grounded in YOUR data (not hallucinating)
 * - Works on tiny hardware (Raspberry Pi 2011!)
 * 
 * This is the SAME architecture used by:
 * - ChatGPT when searching your uploaded documents
 * - Enterprise AI assistants searching company docs
 * - Customer support bots searching help articles
 * 
 * And you built it. In PHP. On a 12-year-old board.
 * With complete transparency - you can inspect every step.
 * 
 * Understanding beats horsepower. 🍕🚀
 */
?>
