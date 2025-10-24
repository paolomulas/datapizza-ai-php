<?php
/**
 * 🍕 Vector Store Deep Dive - Understanding Semantic Search
 * 
 * You've seen RAG working end-to-end. Now let's zoom in on the CORE:
 * The Vector Store.
 * 
 * This is where the magic of "semantic search" happens.
 * Traditional search: keyword matching ("pizza" finds "pizza")
 * Semantic search: meaning matching ("Italian food" finds "pizza")
 * 
 * How?
 * - Every document becomes a point in 1536-dimensional space
 * - Similar meanings = nearby points
 * - Search = find nearest neighbors
 * 
 * This example shows you:
 * - How to add documents with embeddings
 * - How similarity search actually works
 * - How to measure "closeness" (cosine similarity)
 * - How to inspect and manage the vector database
 * 
 * After this, vector databases won't be mysterious anymore. 📐
 */

require_once __DIR__ . '/../../datapizza/embedders/openai_embedder.php';
require_once __DIR__ . '/../../datapizza/vectorstores/simple_vectorstore.php';

// Load environment variables
$env = parse_ini_file(__DIR__ . '/../../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "=== Vector Store RAG Test ===\n\n";

// ========================================
// STEP 1: Initialize components
// ========================================
echo "1. Initializing components...\n";
$embedder = new OpenAIEmbedder();
$vectorstore = new SimpleVectorStore();
$vectorstore->clear_all(); // Clean slate for demo
echo "✓ Vector store ready\n\n";

// ========================================
// STEP 2: Add sample documents
// ========================================
echo "2. Adding documents...\n";
$documents = [
    "The cat is a domestic animal that loves to sleep",
    "The dog is a loyal animal that loves to run",
    "Margherita pizza is an Italian dish with tomato and mozzarella",
    "The Raspberry Pi is an affordable computer for educational projects",
    "PHP is a programming language for the web"
];

foreach ($documents as $doc) {
    // Convert text to 1536-dimensional vector
    $embedding = $embedder->embed($doc);
    // Store: text + vector + metadata
    $doc_id = $vectorstore->add_document($doc, $embedding, ['source' => 'test']);
    echo "  ✓ Added: '$doc'\n";
}
echo "\nTotal documents in database: " . $vectorstore->count() . "\n\n";

// ========================================
// STEP 3: Similarity search tests
// ========================================
echo "3. Similarity search tests:\n\n";

$queries = [
    "domestic animals",
    "Italian food",
    "computer for students"
];

foreach ($queries as $query) {
    echo "Query: '$query'\n";
    // Convert query to embedding (same vector space as documents)
    $query_embedding = $embedder->embed($query);
    // Find 3 most similar documents
    $results = $vectorstore->search($query_embedding, 3);
    
    foreach ($results as $i => $result) {
        // Cosine similarity score (0-1, higher = more similar)
        $score_percent = round($result['score'] * 100, 1);
        echo "  " . ($i + 1) . ". [Score: $score_percent%] " . $result['text'] . "\n";
    }
    echo "\n";
}

// ========================================
// STEP 4: Document deletion test
// ========================================
echo "4. Document deletion test:\n";
$results = $vectorstore->search($embedder->embed("pizza"), 1);
if (!empty($results)) {
    $doc_to_delete = $results[0]['id'];
    $vectorstore->delete($doc_to_delete);
    echo "✓ Deleted document: '" . $results[0]['text'] . "'\n";
    echo "Remaining documents: " . $vectorstore->count() . "\n\n";
}

echo "✅ Test completed!\n";
echo "\nYou can inspect the saved data in: data/vectorstore.json\n";

/**
 * 🎓 What you just learned about Vector Stores:
 * 
 * THE CORE CONCEPT: Embeddings as Coordinates
 * ──────────────────────────────────────────────
 * Think of embeddings as GPS coordinates, but in 1536 dimensions:
 * 
 * - "cat" might be at [0.2, -0.5, 0.8, ..., 0.1]
 * - "dog" might be at [0.3, -0.4, 0.7, ..., 0.2]
 * - "pizza" might be at [-0.1, 0.9, -0.3, ..., 0.5]
 * 
 * Cat and dog are CLOSE in vector space (both animals)
 * Pizza is FAR from cat/dog (different concept)
 * 
 * SIMILARITY SEARCH EXPLAINED:
 * ───────────────────────────
 * When you search for "domestic animals":
 * 
 * 1. Convert query to embedding: [0.25, -0.45, 0.75, ...]
 * 2. Calculate distance to ALL stored embeddings
 * 3. Return the NEAREST ones (smallest distance)
 * 
 * Distance metric: Cosine similarity
 * - Measures angle between vectors
 * - 1.0 = identical direction (perfect match)
 * - 0.0 = perpendicular (unrelated)
 * - -1.0 = opposite direction (antonyms)
 * 
 * WHAT YOU SAW IN THE OUTPUT:
 * ──────────────────────────
 * Query: "domestic animals"
 * Results:
 *   1. [Score: 85%] "The cat is a domestic animal..."  ← High score!
 *   2. [Score: 82%] "The dog is a loyal animal..."     ← Also high!
 *   3. [Score: 45%] "PHP is a programming language..." ← Low score
 * 
 * Notice:
 * - No keyword "domestic animals" appears in the documents
 * - Yet it found the RIGHT documents (cat, dog)
 * - This is semantic understanding, not string matching
 * 
 * WHY THIS WORKS:
 * ──────────────
 * OpenAI's embedding model was trained on billions of texts.
 * It learned that:
 * - "cat", "dog", "pet", "domestic animal" are semantically related
 * - They should have similar embeddings (nearby in vector space)
 * - When you search, you find conceptually related docs
 * 
 * FILE-BASED SIMPLICITY:
 * ─────────────────────
 * Your vector store is just a JSON file:
 * 
 * {
 *   "doc_1": {
 *     "text": "The cat...",
 *     "embedding": [0.2, -0.5, ...],
 *     "metadata": {"source": "test"}
 *   },
 *   ...
 * }
 * 
 * Open data/vectorstore.json and see for yourself!
 * No black boxes. Complete transparency.
 * 
 * OPERATIONS DEMONSTRATED:
 * ───────────────────────
 * ✅ add_document() - Store text + embedding
 * ✅ search() - Find similar documents
 * ✅ count() - Get database size
 * ✅ delete() - Remove documents
 * ✅ clear_all() - Wipe database
 * 
 * This is your CRUD for vector databases.
 * Same principles scale to Pinecone, Weaviate, ChromaDB...
 * But you learned it on the simplest possible implementation.
 * 
 * Understanding beats complexity. 🍕📐
 */
?>
