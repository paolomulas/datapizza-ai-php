<?php
require_once __DIR__ . '/../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../datapizza/tools/calculator.php';
require_once __DIR__ . '/../datapizza/tools/datetime_tool.php';
require_once __DIR__ . '/../datapizza/tools/file_reader.php';

// Carica variabili d'ambiente
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "=== Test ReAct Agent ===\n\n";

// Crea tools disponibili
$tools = [
    new Calculator(),
    new DateTimeTool(),
    new FileReader()
];

// Crea agent con verbose=true per vedere il reasoning
$agent = new ReActAgent(
    tools: $tools,
    llm_provider: 'openai',
    model: 'gpt-4o-mini',
    max_iterations: 5,
    verbose: true
);

// Test 1: Calcolo semplice
echo "Test 1: Calcolo matematico\n";
echo str_repeat("=", 50) . "\n";
$response = $agent->run("Quanto fa 15.7% di 8432?");
echo "\n🎯 Risposta finale: $response\n\n";

// Test 2: Operazione su date
echo "\nTest 2: Calcolo date\n";
echo str_repeat("=", 50) . "\n";
$response = $agent->run("Quanti giorni mancano a Natale 2025?");
echo "\n🎯 Risposta finale: $response\n\n";

// Test 3: Multi-step reasoning
echo "\nTest 3: Reasoning multi-step\n";
echo str_repeat("=", 50) . "\n";
$response = $agent->run("Calcola quanti giorni lavorativi (escludendo sabato e domenica) mancano a Natale 2025");
echo "\n🎯 Risposta finale: $response\n\n";

echo "✅ Test completato!\n";
