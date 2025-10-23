<?php
require_once __DIR__ . '/../datapizza/embedders/openai_embedder.php';

// Carica variabili d'ambiente
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "=== Test OpenAI Embedder ===\n\n";

// Crea l'embedder
$embedder = new OpenAIEmbedder();

// Test 1: Embedding singolo
echo "1. Test embedding singolo:\n";
$text = "Il gatto dorme sul divano";
$embedding = $embedder->embed($text);
echo "Testo: '$text'\n";
echo "Dimensioni vettore: " . count($embedding) . "\n";
echo "Primi 5 valori: [" . implode(", ", array_slice($embedding, 0, 5)) . "...]\n\n";

// Test 2: Batch embedding
echo "2. Test batch embedding:\n";
$texts = [
    "Il gatto dorme sul divano",
    "Il cane corre nel parco",
    "La pizza è deliziosa"
];
$embeddings = $embedder->embed_batch($texts);
echo "Numero di testi: " . count($texts) . "\n";
echo "Numero di embeddings generati: " . count($embeddings) . "\n";
foreach ($texts as $i => $text) {
    echo "  - Testo $i: '$text' -> Vettore di " . count($embeddings[$i]) . " dimensioni\n";
}

echo "\n✅ Test completato!\n";
?>
