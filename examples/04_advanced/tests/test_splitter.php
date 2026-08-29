<?php
/**
 * Test Node Splitter
 */

require_once __DIR__ . '/../../../datapizza/modules/splitters/node_splitter.php';

// Carica env
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - Node Splitter Demo             ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$document = "
L'intelligenza artificiale (AI) è una branca dell'informatica che si occupa 
della creazione di sistemi in grado di eseguire compiti che normalmente 
richiederebbero l'intelligenza umana. Questi compiti includono il riconoscimento 
vocale, la percezione visiva, il processo decisionale e la traduzione linguistica.

Il campo dell'AI è nato nel 1956 durante una conferenza al Dartmouth College. 
Da allora ha attraversato diversi periodi di entusiasmo e disillusione, noti 
come 'AI winters'. Oggi viviamo in una nuova primavera dell'AI, grazie ai 
progressi nell'apprendimento automatico e in particolare nel deep learning.

Il machine learning è un sottoinsieme dell'AI che si concentra sulla capacità 
dei computer di apprendere dai dati senza essere esplicitamente programmati. 
Gli algoritmi di machine learning migliorano le loro prestazioni con l'esperienza.
";

echo "📚 Fase 1: Preparazione documento...\n";
$doc_length = strlen($document);
echo " ✓ Documento caricato: $doc_length caratteri\n\n";

echo "🔪 Fase 2: Chunking (max=300, overlap=50)...\n\n";
$chunks = splitter_split($document, 300, 50);

echo " ✓ Chunk generati: " . count($chunks) . "\n";
echo str_repeat("═", 54) . "\n\n";

foreach ($chunks as $index => $chunk) {
    echo "📄 Chunk " . ($index + 1) . "/" . count($chunks) . "\n";
    echo str_repeat("─", 54) . "\n";
    echo "Caratteri: " . strlen($chunk) . "\n";
    echo "Preview: " . substr($chunk, 0, 80) . "...\n\n";
}

echo str_repeat("═", 54) . "\n";
echo "🔬 Fase 3: Confronto overlap...\n\n";

$c1 = splitter_split($document, 300, 0);
echo "Overlap 0: " . count($c1) . " chunk\n";

$c2 = splitter_split($document, 300, 50);
echo "Overlap 50: " . count($c2) . " chunk\n";

$c3 = splitter_split($document, 300, 100);
echo "Overlap 100: " . count($c3) . " chunk\n\n";

echo "✅ Test completato!\n";
