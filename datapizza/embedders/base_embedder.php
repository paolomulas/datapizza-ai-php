<?php

/**
 * 🍕 Datapizza-AI PHP - Base Embedder
 * 
 * Abstract foundation for all text embedding generators.
 * Embeddings convert text into numerical vectors that capture
 * semantic meaning - the foundation of semantic search and RAG.
 * 
 * Educational concepts:
 * - Text embeddings represent semantic similarity as vector proximity
 * - Different models produce different dimensional vectors
 * - Batch processing reduces API calls and improves efficiency
 * 
 * Why embeddings matter:
 * Vector databases use embeddings to find relevant context for AI.
 * Instead of keyword matching, we match meaning.
 * 
 * Example:
 * "king" - "man" + "woman" ≈ "queen" (vector arithmetic!)
 */

abstract class BaseEmbedder {
    
    /**
     * Generates embedding vector for a single text
     * 
     * This is the core method that each embedder must implement.
     * It converts a string of text into a numerical vector that
     * represents its semantic meaning.
     * 
     * Educational note:
     * Embeddings are NOT word counts or TF-IDF. They're learned
     * representations from neural networks trained on massive text.
     * Words with similar meanings end up close in vector space.
     * 
     * @param string $text Text to convert into embedding vector
     * @return array Float vector representing the text's semantic meaning
     */
    abstract public function embed($text);
    
    /**
     * Generates embeddings for multiple texts efficiently
     * 
     * This default implementation loops through texts one by one.
     * Provider-specific embedders should override this with batch APIs
     * when available (OpenAI, Cohere, etc. support batch embedding).
     * 
     * Educational concept - Why batch processing matters:
     * - Reduces HTTP overhead (1 request instead of N)
     * - Lower latency (parallel processing server-side)
     * - Often cheaper per-token with batch APIs
     * - Critical for Raspberry Pi with limited bandwidth
     * 
     * @param array $texts Array of text strings
     * @return array Array of embedding vectors (same order as input)
     */
    public function embed_batch($texts) {
        $embeddings = [];
        
        // Default implementation: process each text individually
        // Subclasses should override this with native batch API calls
        foreach ($texts as $text) {
            $embeddings[] = $this->embed($text);
        }
        
        return $embeddings;
    }
    
    /**
     * Returns the dimensionality of produced embeddings
     * 
     * This is critical for vector store compatibility.
     * All embeddings in a vector store must have the same dimensions.
     * You cannot search 1536-dim embeddings in a 768-dim store.
     * 
     * Educational note:
     * Higher dimensions can capture more nuance but:
     * - Require more storage
     * - Slower similarity calculations
     * - Diminishing returns above ~1536 dimensions
     * 
     * Common dimensions:
     * - OpenAI text-embedding-3-small: 1536 (or 512 reduced)
     * - OpenAI text-embedding-3-large: 3072 (or 256 reduced)
     * - Sentence Transformers: 384-768
     * 
     * @return int Number of dimensions in embedding vectors
     */
    abstract public function get_dimensions();
}
