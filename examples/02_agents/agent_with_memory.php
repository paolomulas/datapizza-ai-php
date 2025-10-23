<?php
require_once __DIR__ . '/../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../datapizza/memory/conversation_memory.php';
require_once __DIR__ . '/../datapizza/tools/calculator.php';

// Setup env
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) putenv("$key=$value");

echo "=== Test ReactAgent + Conversation Memory ===\n\n";

$session_id = 'demo_session_' . date('His');
echo "Sessione: $session_id\n\n";

// Setup agent
$agent = new ReactAgent(
    tools: [new Calculator()],
    llm_provider: 'openai',
    model: 'gpt-4o-mini',
    max_iterations: 3
);

echo "Test 1: Prima query (agent impara nome)\n";
echo str_repeat("-", 60) . "\n";

$query1 = "Ciao! Mi chiamo Paolo e sono uno studente di AI";
echo "User: $query1\n";

// Prima query - context vuoto
$context1 = memory_get_context($session_id, 'Sei un tutor AI educativo che ricorda le conversazioni');
$response1 = $agent->run($query1, context: $context1);

echo "Agent: $response1\n";

// Salva in memoria
memory_add($session_id, 'user', $query1);
memory_add($session_id, 'assistant', $response1);

echo "\n⏳ Pausa 2 secondi...\n\n";
sleep(2);

echo "Test 2: Seconda query (agent dovrebbe ricordare nome)\n";
echo str_repeat("-", 60) . "\n";

$query2 = "Come mi chiamo? E cosa studio?";
echo "User: $query2\n";

// Seconda query - CON context memoria
$context2 = memory_get_context($session_id, 'Sei un tutor AI educativo che ricorda le conversazioni');
$response2 = $agent->run($query2, context: $context2);

echo "Agent: $response2\n";

// Salva in memoria
memory_add($session_id, 'user', $query2);
memory_add($session_id, 'assistant', $response2);

echo "\n⏳ Pausa 2 secondi...\n\n";
sleep(2);

echo "Test 3: Query con calcolo (usa tool + memoria)\n";
echo str_repeat("-", 60) . "\n";

$query3 = "Calcola la radice quadrata di 144 e dimmi se ricordi ancora il mio nome";
echo "User: $query3\n";

$context3 = memory_get_context($session_id, 'Sei un tutor AI educativo che ricorda le conversazioni');
$response3 = $agent->run($query3, context: $context3);

echo "Agent: $response3\n";

// Salva in memoria
memory_add($session_id, 'user', $query3);
memory_add($session_id, 'assistant', $response3);

// Statistiche finali
echo "\n" . str_repeat("=", 60) . "\n";
echo "Statistiche Conversazione:\n";
$stats = memory_get_stats($session_id);
echo "- Messaggi salvati: {$stats['total_messages']}\n";
echo "- Dimensione file: {$stats['file_size_kb']} KB\n";
echo "- Percorso: {$stats['file_path']}\n";

echo "\n✅ Test completato! Agent ora ha memoria persistente.\n";
