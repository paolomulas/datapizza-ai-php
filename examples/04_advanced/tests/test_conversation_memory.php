<?php
require_once __DIR__ . '/../datapizza/memory/conversation_memory.php';

echo "=== Test Conversation Memory (Stile VectorStore) ===\n\n";

$session = 'student_paolo_001';

// FIX: Pulisci memoria prima del test
echo "Pulizia memoria precedente...\n";
memory_clear($session);
echo "✅ Memoria pulita\n\n";

// Test 1: Aggiungi messaggi
echo "Test 1: Conversazione educativa\n";
echo str_repeat("-", 60) . "\n";

memory_add($session, 'user', 'Ciao! Sono Paolo, studente di AI');
memory_add($session, 'assistant', 'Ciao Paolo! Piacere, sono qui per aiutarti con AI');

memory_add($session, 'user', 'Spiegami cosa è un LLM');
memory_add($session, 'assistant', 'Un LLM è un modello di linguaggio su larga scala addestrato su miliardi di parametri');

memory_add($session, 'user', 'Quanti parametri ha GPT-4?');
memory_add($session, 'assistant', 'GPT-4 ha circa 1.76T parametri in architettura MOE');

echo "✅ 6 messaggi aggiunti\n\n";

// Test 2: Recupera context
echo "Test 2: Context per LLM\n";
echo str_repeat("-", 60) . "\n";

$context = memory_get_context($session, 'Sei un tutor AI educativo');

echo "Totale messaggi in context: " . count($context) . "\n\n";

foreach ($context as $i => $msg) {
    $preview = strlen($msg['content']) > 50 ? 
        substr($msg['content'], 0, 50) . '...' : 
        $msg['content'];
    echo sprintf("[%d] %s: %s\n", $i, strtoupper($msg['role']), $preview);
}

// Test 3: Statistiche
echo "\nTest 3: Statistiche memoria\n";
echo str_repeat("-", 60) . "\n";

$stats = memory_get_stats($session);
echo "Messaggi: {$stats['total_messages']}\n";
echo "Dimensione: {$stats['file_size_kb']} KB\n";
echo "Percorso: {$stats['file_path']}\n";

// Test 4: Verifica file JSON (educational)
echo "\nTest 4: Contenuto file JSON (educational)\n";
echo str_repeat("-", 60) . "\n";
if (file_exists($stats['file_path'])) {
    echo "File JSON creato correttamente:\n";
    echo file_get_contents($stats['file_path']);
}

echo "\n\n✅ Test completato! Conversazione salvata su disco.\n";
