<?php

/**
 * 🍕 Datapizza-AI PHP - Retrieval Utilities
 * 
 * Utility functions for Retrieval-Augmented Generation (RAG).
 * These functions help process and refine vector search results
 * before sending them to the LLM.
 * 
 * Educational concepts:
 * - RAG pipeline: retrieve relevant docs, then generate answer
 * - Hybrid search: semantic (vectors) + keyword matching
 * - Quality filtering: threshold-based relevance
 * - Metadata filtering: structured document filtering
 * - Context formatting: preparing results for LLM consumption
 * 
 * Why these utilities matter:
 * Raw vector search returns similar documents, but we often need to:
 * - Filter out low-quality matches (threshold)
 * - Boost exact keyword matches (hybrid ranking)
 * - Filter by document type/category (metadata)
 * - Format nicely for LLM context (numbered citations)
 */

/**
 * Main retrieval function with similarity threshold filtering
 * 
 * This is the core RAG retrieval operation:
 * 1. Embeds the user's query into a vector
 * 2. Searches vectorstore for similar documents
 * 3. Filters results by minimum similarity threshold
 * 
 * Educational concept - Similarity threshold:
 * Not all "similar" documents are actually relevant.
 * Typical thresholds:
 * - 0.9+: Very high relevance (almost identical)
 * - 0.7-0.9: Good relevance (clearly related)
 * - 0.5-0.7: Weak relevance (loosely related)
 * - <0.5: Probably not relevant
 * 
 * Example:
 * Query: "How to reset my password?"
 * Results:
 * - "Password reset guide" (score: 0.92) ✅ Pass threshold
 * - "Account settings" (score: 0.75) ✅ Pass threshold
 * - "Privacy policy" (score: 0.45) ❌ Below threshold
 * 
 * @param object $embedder Embedder instance to convert query to vector
 * @param object $vectorstore Vector database instance
 * @param string $query User's search query
 * @param int $top_k Initial number of results to retrieve
 * @param float $threshold Minimum similarity score (0.0 to 1.0)
 * @return array Filtered results above threshold
 */
function retrieval_search($embedder, $vectorstore, $query, $top_k = 5, $threshold = 0.7) {
    // Step 1: Convert query text to embedding vector
    $query_embedding = $embedder->embed($query);
    
    // Step 2: Search vectorstore for similar documents
    $results = $vectorstore->search($query_embedding, $top_k);
    
    // Step 3: Filter by similarity threshold
    $filtered = array();
    foreach ($results as $result) {
        $similarity = isset($result['score']) ? $result['score'] : 1.0;
        
        // Only keep results above threshold
        if ($similarity >= $threshold) {
            $filtered[] = $result;
        }
    }
    
    return $filtered;
}

/**
 * Formats retrieval results for LLM context
 * 
 * Converts search results into a numbered, citation-friendly format
 * that LLMs can reference in their responses.
 * 
 * Educational benefit:
 * When the LLM says "According to document [2]...", the user
 * knows exactly which source was used. This provides:
 * - Transparency (AI isn't making things up)
 * - Traceability (can verify the source)
 * - Credibility (backed by actual documents)
 * 
 * Format example:
 * Input: [{text: "Python is..."}, {text: "PHP is..."}]
 * Output:
 * "[1] Python is a programming language...
 * 
 *  [2] PHP is a server-side language..."
 * 
 * @param array $results Array of search results from vectorstore
 * @return string Formatted context string with numbered documents
 */
function retrieval_format_context($results) {
    $texts = array();
    
    foreach ($results as $idx => $result) {
        $num = $idx + 1;  // Start numbering from 1, not 0
        $text = isset($result['text']) ? $result['text'] : '';
        
        // Format: [1] Document text
        $texts[] = "[$num] $text";
    }
    
    // Join with double newlines for readability
    return implode("\n\n", $texts);
}

/**
 * Filters results by metadata field value
 * 
 * Educational concept - Metadata filtering:
 * Sometimes you want only certain types of documents.
 * Examples:
 * - Only PDFs: filter_metadata($results, 'type', 'pdf')
 * - Only from 2024: filter_metadata($results, 'year', '2024')
 * - Only API docs: filter_metadata($results, 'category', 'api')
 * 
 * This is like SQL's WHERE clause but for vector search results.
 * 
 * Use case:
 * User asks: "How to use the API?"
 * You want only documents tagged with category='api',
 * not blog posts or tutorials.
 * 
 * @param array $results Search results with metadata
 * @param string $key Metadata field name to filter on
 * @param mixed $value Value to match (exact match)
 * @return array Filtered results where metadata[$key] == $value
 */
function retrieval_filter_metadata($results, $key, $value) {
    $filtered = array();
    
    foreach ($results as $result) {
        // Check if metadata field exists and matches value
        if (isset($result['metadata'][$key]) && $result['metadata'][$key] == $value) {
            $filtered[] = $result;
        }
    }
    
    return $filtered;
}

/**
 * Re-ranks results by keyword matching (hybrid retrieval)
 * 
 * Educational concept - Hybrid search:
 * Semantic search (embeddings) is great but not perfect.
 * Sometimes exact keyword matches are important.
 * 
 * Example problem:
 * Query: "OpenAI API key configuration"
 * Semantic might rank "API authentication guide" first
 * But we want exact match "OpenAI API key setup" higher
 * 
 * Solution: Hybrid ranking
 * 1. Use semantic similarity as base score
 * 2. Add keyword matching score as boost
 * 3. Re-sort by combined score
 * 
 * This function counts keyword matches (words >3 chars) and
 * re-ranks results by keyword frequency.
 * 
 * Combines benefits:
 * - Semantic: Finds "car" when you search "automobile"
 * - Keywords: Boosts exact match "automobile repair guide"
 * 
 * @param array $results Search results to re-rank
 * @param string $query Original query string
 * @return array Re-ranked results (highest keyword score first)
 */
function retrieval_rerank_keywords($results, $query) {
    // Extract keywords from query (lowercase for matching)
    $keywords = explode(' ', strtolower($query));
    
    // Score each result by keyword frequency
    foreach ($results as $key => $result) {
        $text = strtolower($result['text']);
        $score = 0;
        
        // Count how many query keywords appear in document
        foreach ($keywords as $keyword) {
            // Only count words longer than 3 chars (skip "the", "and", etc.)
            if (strlen($keyword) > 3 && strpos($text, $keyword) !== false) {
                $score++;
            }
        }
        
        // Add keyword score to result
        $results[$key]['keyword_score'] = $score;
    }
    
    // Re-sort by keyword score (descending)
    usort($results, function($a, $b) {
        return $b['keyword_score'] - $a['keyword_score'];
    });
    
    return $results;
}

/**
 * Limits results to top N items
 * 
 * Simple utility to truncate results array.
 * Useful after re-ranking or filtering to ensure you don't
 * send too much context to the LLM.
 * 
 * Educational note - Context window limits:
 * LLMs have token limits (e.g., GPT-4: 8K/32K/128K tokens).
 * If you send 100 documents, you might hit the limit.
 * Usually top 3-5 documents are sufficient for good answers.
 * 
 * @param array $results Results array
 * @param int $n Maximum number of results to keep
 * @return array First N results (or fewer if array is shorter)
 */
function retrieval_top_n($results, $n) {
    return array_slice($results, 0, $n);
}
