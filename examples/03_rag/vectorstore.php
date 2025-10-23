<?php
require_once __DIR__ . '/../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../datapizza/vectorstores/simple_vectorstore.php';

// Carica variabili d'ambiente
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "=== Test Vector Store con RAG ===\n\n";

// 1. Crea embedder e vector store
echo "1. Inizializzazione componenti...\n";
$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore();
$vectorstore->clear_all(); // Pulisci database precedente
echo "✓ Vector store pronto\n\n";

// 2. Aggiungi documenti di esempio
echo "2. Aggiunta documenti...\n";
$documents = [
    "Il gatto è un animale domestico che ama dormire",
    "Il cane è un animale fedele che ama correre",
    "La pizza margherita è un piatto italiano con pomodoro e mozzarella",
    "Il Raspberry Pi è un computer economico per progetti educativi",
    "PHP è un linguaggio di programmazione per il web"
];

foreach ($documents as $doc) {
    $embedding = $embedder->embed($doc);
    $doc_id = $vectorstore->add_document($doc, $embedding, ['source' => 'test']);
    echo "  ✓ Aggiunto: '$doc'\n";
}
echo "\nTotale documenti nel database: " . $vectorstore->count() . "\n\n";

// 3. Test similarity search
echo "3. Test ricerca per similarità:\n\n";

$queries = [
    "animali domestici",
    "cibo italiano",
    "computer per studenti"
];

foreach ($queries as $query) {
    echo "Query: '$query'\n";
    $query_embedding = $embedder->embed($query);
    $results = $vectorstore->search($query_embedding, 3);
    
    foreach ($results as $i => $result) {
        $score_percent = round($result['score'] * 100, 1);
        echo "  " . ($i + 1) . ". [Score: $score_percent%] " . $result['text'] . "\n";
    }
    echo "\n";
}

// 4. Test eliminazione documento
echo "4. Test eliminazione documento:\n";
$results = $vectorstore->search($embedder->embed("pizza"), 1);
if (!empty($results)) {
    $doc_to_delete = $results[0]['id'];
    $vectorstore->delete($doc_to_delete);
    echo "✓ Eliminato documento: '" . $results[0]['text'] . "'\n";
    echo "Documenti rimanenti: " . $vectorstore->count() . "\n\n";
}

echo "✅ Test completato!\n";
echo "\nPuoi vedere i dati salvati in: data/vectorstore.json\n";
?>
