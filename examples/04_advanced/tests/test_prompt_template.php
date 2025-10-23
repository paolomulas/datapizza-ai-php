<?php
/**
 * Test Prompt Templates
 */

require_once __DIR__ . '/../datapizza/modules/prompt/chat_prompt_template.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - Prompt Template Demo           ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Test 1: Sostituzione variabili base
echo "📝 Test 1: Sostituzione variabili base\n";
echo str_repeat("═", 54) . "\n";

$template = "Hello {{name}}! You are {{age}} years old and live in {{city}}.";
$result = prompt_format($template, array(
    'name' => 'Paolo',
    'age' => 30,
    'city' => 'Cagliari'
));

echo "Template: $template\n";
echo "Risultato: $result\n\n";

// Test 2: Template RAG semplice
echo "📝 Test 2: Template RAG semplice\n";
echo str_repeat("═", 54) . "\n";

$context = "DataPizza-AI-PHP è un framework PHP per sviluppare applicazioni AI. Include supporto per RAG, embeddings e agenti.";
$query = "Cos'è DataPizza-AI-PHP?";

$prompt = prompt_rag($context, $query);
echo "Prompt generato:\n";
echo str_repeat("-", 54) . "\n";
echo $prompt . "\n\n";

// Test 3: Template chat OpenAI
echo "📝 Test 3: Template chat (formato OpenAI)\n";
echo str_repeat("═", 54) . "\n";

$messages = prompt_chat(
    "You are a helpful PHP programming assistant.",
    "Come funziona il RAG?"
);

echo "Messaggi generati:\n";
foreach ($messages as $msg) {
    echo "  [{$msg['role']}]: {$msg['content']}\n";
}
echo "\n";

// Test 4: Template RAG avanzato
echo "📝 Test 4: Template RAG avanzato con documenti\n";
echo str_repeat("═", 54) . "\n";

$documents = array(
    "Il framework supporta 6 provider LLM diversi.",
    "Include tools come Calculator e Wikipedia.",
    "Ha integration native con n8n e MCP."
);

$advanced = prompt_rag_advanced(
    $documents,
    "Quali sono le features principali?",
    "Focus on technical capabilities."
);

echo "Prompt avanzato:\n";
echo str_repeat("-", 54) . "\n";
echo substr($advanced, 0, 300) . "...\n\n";

// Test 5: Array handling
echo "📝 Test 5: Gestione array\n";
echo str_repeat("═", 54) . "\n";

$template_list = "Features:\n{{features}}";
$result_list = prompt_format($template_list, array(
    'features' => array('RAG', 'Embeddings', 'Agents', 'Tools')
));

echo $result_list . "\n\n";

echo "✅ Test completati!\n";
echo "\n💡 I template sono pronti per essere usati con LLM!\n";
