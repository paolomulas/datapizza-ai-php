<?php

/**
 * 🍕 Datapizza-AI PHP - Base VectorStore
 * 
 * Abstract foundation for vector storage implementations.
 * Vector stores are databases optimized for similarity search on embeddings.
 * 
 * Educational concepts:
 * - Vector databases store numerical representations (embeddings)
 * - Similarity search finds "nearest neighbors" in vector space
 * - Abstract classes define contracts for different backends
 * - Enables RAG (Retrieval-Augmented Generation) pattern
 * 
 * What is a vector database?
 * Traditional DB: Stores exact data, searches for exact matches
 * Vector DB: Stores embeddings (vectors), searches for similar meanings
 * 
 * Example:
 * Query: "How to reset password?"
 * Vector DB finds:
 * 1. "Password reset instructions" (score: 0.92)
 * 2. "Change your password" (score: 0.87)
 * 3. "Account recovery guide" (score: 0.81)
 * 
 * Even though exact words don't match, meanings are similar!
 */

abstract class BaseVectorStore {
    
    /**
     * Adds a document with its embedding to the store
     * 
     * Educational flow:
     * 1. Embed document text → get 1536-dim vector
     * 2. Store: {text, embedding, metadata, timestamp}
     * 3. Return unique document ID
     * 
     * @param string $text Original document text (for retrieval)
     * @param array $embedding Numerical vector (e.g., 1536 floats)
     * @param array $metadata Optional metadata (author, date, tags, etc.)
     * @return string Unique document ID
     */
    abstract public function add_document($text, $embedding, $metadata = []);
    
    /**
     * Searches for documents similar to query embedding
     * 
     * Educational concept - Similarity search:
     * 1. Calculate similarity between query vector and ALL stored vectors
     * 2. Sort results by similarity score (highest first)
     * 3. Return top K most similar documents
     * 
     * Similarity metric: Cosine similarity
     * - Measures angle between vectors
     * - Range: -1 (opposite) to 1 (identical)
     * - 0.9+ = very similar
     * - 0.7-0.9 = related
     * - <0.7 = loosely related
     * 
     * @param array $query_embedding Query vector to search for
     * @param int $top_k Number of results to return (default: 5)
     * @return array Array of results [{id, text, score, metadata}, ...]
     */
    abstract public function search($query_embedding, $top_k = 5);
    
    /**
     * Deletes a document by ID
     * 
     * @param string $doc_id Document ID to delete
     * @return bool True if deleted, false if not found
     */
    abstract public function delete($doc_id);
    
    /**
     * Clears entire vectorstore
     * 
     * Educational note:
     * Use carefully! This permanently deletes all documents.
     * Useful for:
     * - Starting fresh in development
     * - Resetting test data
     * - Changing embedding dimensions (incompatible vectors)
     * 
     * @return bool True on success
     */
    abstract public function clear_all();
    
    /**
     * Returns total number of documents stored
     * 
     * @return int Document count
     */
    abstract public function count();
}
