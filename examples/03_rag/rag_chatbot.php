<?php
/**
 * demo_rag_chatbot.php - Demo completa di RAG Chatbot
 * 
 * Questa demo mostra un chatbot che:
 * 1. Ha una knowledge base (documenti caricati nel vector store)
 * 2. Può cercare informazioni rilevanti
 * 3. Può usare altri tools (calculator, datetime)
 * 4. Risponde usando RAG pattern
 */

require_once __DIR__ . '/../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../datapizza/vectorstores/simple_vectorstore.php';
require_once __DIR__ . '/../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../datapizza/tools/calculator.php';
require_once __DIR__ . '/../datapizza/tools/datetime_tool.php';
require_once __DIR__ . '/../datapizza/tools/rag_search.php';

// Carica variabili d'ambiente
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza RAG Chatbot - Demo Completa       ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// ========================================
// FASE 1: Inizializzazione componenti
// ========================================
echo "📦 Fase 1: Inizializzazione componenti...\n";

$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore(__DIR__ . '/../data/rag_demo.json');
$vectorstore->clear_all(); // Pulisci per demo pulita

echo "   ✓ Embedder inizializzato\n";
echo "   ✓ Vector store inizializzato\n\n";

// ========================================
// FASE 2: Caricamento knowledge base
// ========================================
echo "📚 Fase 2: Caricamento knowledge base...\n";

$knowledge_base = [
    [
        'text' => 'DataPizza è un framework PHP per sviluppare applicazioni AI. Include clients per OpenAI e DeepSeek, supporto per embeddings, vector stores, tools e agents con pattern ReAct.',
        'metadata' => ['source' => 'docs', 'topic' => 'overview']
    ],
    [
        'text' => 'Il Raspberry Pi Model B Rev 2 ha 512 MB di RAM, CPU ARM1176 single-core ARMv6 a 700MHz, e fu rilasciato nel 2011. È ideale per progetti educativi.',
        'metadata' => ['source' => 'docs', 'topic' => 'hardware']
    ],
    [
        'text' => 'Il pattern ReAct (Reason + Act) permette agli AI agents di alternare tra ragionamento (Thought) e azione (Action con tools). Dopo ogni azione, l\'agent riceve un\'Observation e decide se continuare o fornire la Final Answer.',
        'metadata' => ['source' => 'docs', 'topic' => 'agents']
    ],
    [
        'text' => 'OpenAI text-embedding-3-small produce vettori di 1536 dimensioni e costa $0.02 per 1 milione di token. È ottimo per RAG applications.',
        'metadata' => ['source' => 'docs', 'topic' => 'embeddings']
    ],
    [
        'text' => 'Il Simple Vector Store salva embeddings in formato JSON sul filesystem. È leggero (funziona con 50 MB RAM) e perfetto per prototipi o hardware limitato come Raspberry Pi vintage.',
        'metadata' => ['source' => 'docs', 'topic' => 'vectorstores']
    ],
    [
        'text' => 'I tools in DataPizza estendono le capacità degli agents. Calculator esegue calcoli, DateTime manipola date, FileReader legge file, e RAGSearch cerca nella knowledge base.',
        'metadata' => ['source' => 'docs', 'topic' => 'tools']
    ]
];

echo "   Caricamento " . count($knowledge_base) . " documenti...\n";

foreach ($knowledge_base as $doc) {
    $embedding = $embedder->embed($doc['text']);
    $vectorstore->add_document($doc['text'], $embedding, $doc['metadata']);
    echo "   ✓ Caricato: " . substr($doc['text'], 0, 50) . "...\n";
}

echo "   ✓ Knowledge base caricata (" . $vectorstore->count() . " documenti)\n\n";

// ========================================
// FASE 3: Creazione Agent RAG
// ========================================
echo "🤖 Fase 3: Creazione RAG Agent...\n";

$tools = [
    new RAGSearch($vectorstore, $embedder),  // Tool principale per RAG
    new Calculator(),
    new DateTimeTool()
];

$agent = new ReActAgent(
    tools: $tools,
    llm_provider: 'openai',
    model: 'gpt-4o-mini',
    max_iterations: 5,
    verbose: false  // Disabilitato per output pulito
);

echo "   ✓ Agent configurato con " . count($tools) . " tools\n\n";

// ========================================
// FASE 4: Demo conversazione
// ========================================
echo "💬 Fase 4: Demo conversazione RAG\n";
echo str_repeat("═", 54) . "\n\n";

// Domande di test
$queries = [
    "Cos'è DataPizza e cosa include?",
    "Quali sono le specifiche del Raspberry Pi Model B?",
    "Spiega il pattern ReAct in modo semplice",
    "Quanto costa usare OpenAI embeddings per 100,000 token?"
];

foreach ($queries as $i => $query) {
    echo "👤 User: $query\n";
    echo str_repeat("-", 54) . "\n";
    
    $start_time = microtime(true);
    $response = $agent->run($query);
    $elapsed = round((microtime(true) - $start_time) * 1000);
    
    echo "🤖 Assistant: $response\n";
    echo "   ⏱️  Tempo risposta: {$elapsed}ms\n\n";
    
    if ($i < count($queries) - 1) {
        echo str_repeat("─", 54) . "\n\n";
    }
}

// ========================================
// FASE 5: Statistiche finali
// ========================================
echo str_repeat("═", 54) . "\n";
echo "📊 Statistiche finali:\n";
echo "   • Documenti nella knowledge base: " . $vectorstore->count() . "\n";
echo "   • Tools disponibili: " . count($tools) . "\n";
echo "   • Queries processate: " . count($queries) . "\n";
echo "\n✅ Demo completata!\n";
echo "\n💡 Suggerimento: Prova a modificare le queries in demo_rag_chatbot.php\n";
echo "   per vedere come l'agent risponde a domande diverse!\n";
