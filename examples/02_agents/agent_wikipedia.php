<?php
/**
 * Test ReactAgent + Wikipedia v1.1 (stile ieri)
 */

require_once __DIR__ . '/../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../datapizza/tools/calculator.php';
require_once __DIR__ . '/../datapizza/tools/datetime_tool.php';
require_once __DIR__ . '/../datapizza/tools/wikipedia_search.php';

// Carica variabili d'ambiente (come ieri)
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "=== Test ReactAgent + Wikipedia v1.1 ===\n";
echo "Raspberry Pi Model B 2011\n\n";

// Crea tools disponibili
$tools = [
    new Calculator(),
    new DateTimeTool(),
    new WikipediaSearchTool()
];

// Crea agent ESATTAMENTE come ieri
$agent = new ReactAgent(
    tools: $tools,
    llm_provider: 'openai',
    model: 'gpt-4o-mini',
    max_iterations: 5,
    verbose: true
);

// Test 1: Wikipedia search
echo "Test 1: Ricerca Wikipedia\n";
echo str_repeat("=", 70) . "\n";
$response = $agent->run("Quanti abitanti ha Milano?");
echo "\n🎯 Risposta finale: $response\n\n";

// Test 2: Calcolo
echo "\nTest 2: Calcolo matematico\n";
echo str_repeat("=", 70) . "\n";
$response = $agent->run("Calcola 15% di 350");
echo "\n🎯 Risposta finale: $response\n\n";

echo "✅ Test completato!\n";
