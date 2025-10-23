<?php

/**
 * 🍕 Datapizza-AI PHP - Simple VectorStore
 * 
 * File-based vector storage using JSON.
 * Implements cosine similarity search in pure PHP.
 * 
 * Educational implementation - Learning focus:
 * This is NOT production-ready for large datasets but excellent for:
 * - Understanding how vector databases work
 * - Learning cosine similarity math
 * - Running on Raspberry Pi without dependencies
 * - Debugging (JSON is human-readable)
 * 
 * Performance characteristics:
 * - Search: O(n) - loops through all documents
 * - Storage: Single JSON file
 * - Good for: <10,000 documents
 * - Not for: Production at scale (use Pinecone, Weaviate, FAISS)
 * 
 * Why file-based?
 * - No database server needed (perfect for Raspberry Pi)
 * - Survives restarts (persistent)
 * - Easy to backup/restore (just copy JSON file)
 * - Debuggable (open file and inspect)
 */

require_once __DIR__ . '/base_vectorstore.php';

class SimpleVectorStore extends BaseVectorStore {
    
    private $documents = [];     // In-memory document storage
    private $storage_path;       // Path to JSON file
    
    /**
     * Constructor - Initializes storage
     * 
     * @param string $storage_path Path to JSON storage file
     */
    public function __construct($storage_path = null) {
        $this->storage_path = $storage_path ?: __DIR__ . '/../../data/vectorstore.json';
        $this->load_from_disk();
    }
    
    /**
     * Loads documents from JSON file into memory
     * 
     * Educational note:
     * We load the entire store into RAM for fast access.
     * This works on Raspberry Pi for small datasets but
     * wouldn't work for millions of documents.
     * 
     * Trade-off:
     * - Fast search (all in RAM)
     * - Limited by available memory
     */
    private function load_from_disk() {
        // Create directory if it doesn't exist
        $dir = dirname($this->storage_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Load existing documents or start with empty array
        if (file_exists($this->storage_path)) {
            $json = file_get_contents($this->storage_path);
            $this->documents = json_decode($json, true) ?: [];
        } else {
            $this->documents = [];
        }
    }
    
    /**
     * Saves documents to JSON file
     * 
     * JSON_PRETTY_PRINT makes file human-readable.
     * In production you'd skip this for smaller files.
     */
    private function save_to_disk() {
        $json = json_encode($this->documents, JSON_PRETTY_PRINT);
        file_put_contents($this->storage_path, $json);
    }
    
    /**
     * Calculates cosine similarity between two vectors
     * 
     * Educational math lesson - Cosine similarity:
     * 
     * Formula: similarity = (A · B) / (||A|| × ||B||)
     * 
     * Where:
     * - A · B = dot product (sum of element-wise multiplication)
     * - ||A|| = magnitude of A (sqrt of sum of squares)
     * - ||B|| = magnitude of B
     * 
     * Example with 2D vectors:
     * A = [3, 4], B = [6, 8]
     * A · B = 3*6 + 4*8 = 18 + 32 = 50
     * ||A|| = sqrt(3² + 4²) = sqrt(25) = 5
     * ||B|| = sqrt(6² + 8²) = sqrt(100) = 10
     * similarity = 50 / (5 * 10) = 50/50 = 1.0 (identical direction!)
     * 
     * Why cosine similarity?
     * - Measures angle, not distance
     * - Works for high-dimensional vectors (1536 dimensions!)
     * - Range -1 to 1 (easy to interpret)
     * - Immune to vector magnitude (scale-invariant)
     * 
     * @param array $vec1 First vector (array of floats)
     * @param array $vec2 Second vector (same length as vec1)
     * @return float Similarity score between -1 and 1
     */
    private function cosine_similarity($vec1, $vec2) {
        $dot_product = 0.0;  // A · B
        $norm1 = 0.0;        // ||A||²
        $norm2 = 0.0;        // ||B||²
        
        // Calculate dot product and norms in single loop
        // This is O(d) where d = vector dimensions
        for ($i = 0; $i < count($vec1); $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $norm1 += $vec1[$i] * $vec1[$i];
            $norm2 += $vec2[$i] * $vec2[$i];
        }
        
        // Calculate magnitudes (square root of sum of squares)
        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);
        
        // Handle zero vectors (no direction = undefined similarity)
        if ($norm1 == 0 || $norm2 == 0) {
            return 0.0;
        }
        
        // Final cosine similarity
        return $dot_product / ($norm1 * $norm2);
    }
    
    /**
     * Adds document to vectorstore
     * 
     * @param string $text Original document text
     * @param array $embedding Numerical vector (e.g., 1536 floats)
     * @param array $metadata Optional metadata
     * @return string Unique document ID
     */
    public function add_document($text, $embedding, $metadata = []) {
        // Generate unique ID with timestamp for uniqueness
        $doc_id = uniqid('doc_', true);
        
        // Store document with all metadata
        $this->documents[$doc_id] = [
            'text' => $text,
            'embedding' => $embedding,
            'metadata' => $metadata,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Persist to disk
        $this->save_to_disk();
        
        return $doc_id;
    }
    
    /**
     * Searches for similar documents
     * 
     * Educational algorithm:
     * 1. Loop through ALL documents (O(n))
     * 2. Calculate cosine similarity with each
     * 3. Sort by similarity (highest first)
     * 4. Return top K results
     * 
     * Performance note:
     * With 1000 documents and 1536-dim vectors:
     * - 1000 similarity calculations
     * - Each calculation: 1536 multiplications + additions
     * - Total: ~1.5M operations
     * - On Raspberry Pi: ~100ms
     * 
     * This is fine for learning but not for 1M+ documents!
     * 
     * @param array $query_embedding Query vector
     * @param int $top_k Number of results to return
     * @return array Top K most similar documents
     */
    public function search($query_embedding, $top_k = 5) {
        $results = [];
        
        // Calculate similarity with each document
        foreach ($this->documents as $doc_id => $doc) {
            $similarity = $this->cosine_similarity($query_embedding, $doc['embedding']);
            
            $results[] = [
                'id' => $doc_id,
                'text' => $doc['text'],
                'score' => $similarity,
                'metadata' => $doc['metadata']
            ];
        }
        
        // Sort by score descending (highest similarity first)
        usort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Return only top K results
        return array_slice($results, 0, $top_k);
    }
    
    /**
     * Deletes document by ID
     * 
     * @param string $doc_id Document ID to delete
     * @return bool True if deleted, false if not found
     */
    public function delete($doc_id) {
        if (isset($this->documents[$doc_id])) {
            unset($this->documents[$doc_id]);
            $this->save_to_disk();
            return true;
        }
        
        return false;
    }
    
    /**
     * Clears all documents
     * 
     * @return bool Always true
     */
    public function clear_all() {
        $this->documents = [];
        $this->save_to_disk();
        return true;
    }
    
    /**
     * Returns document count
     * 
     * @return int Number of documents stored
     */
    public function count() {
        return count($this->documents);
    }
}
