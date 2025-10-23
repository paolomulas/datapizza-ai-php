<?php

/**
 * 🍕 Datapizza-AI PHP - Query Rewriter
 * 
 * Rewrites user queries to improve RAG retrieval quality.
 * 
 * Educational concept - Why rewrite queries?
 * 
 * Problem: Users ask vague or ambiguous questions:
 * - "How to install?" (install what?)
 * - "api key" (what about API keys?)
 * - "error" (which error? where?)
 * 
 * Solution: Rewrite queries to be more specific and searchable!
 * 
 * Rewriting strategies:
 * 1. Expand: Add related keywords ("install" → "install setup configure")
 * 2. Clarify: Add context ("api key" → "how to configure API key")
 * 3. Extract: Find core keywords for better matching
 * 
 * Example:
 * User: "api key"
 * Rewritten: "how to configure API key authentication setup"
 * Better retrieval: finds docs about API key configuration!
 * 
 * When to use:
 * - User queries are short/vague
 * - Retrieval quality is poor
 * - Need to match technical jargon
 */

/**
 * Optimizes query for better retrieval
 * 
 * Educational flow:
 * 1. Clean query (remove punctuation, lowercase)
 * 2. Expand with synonyms and related terms
 * 3. Add common context words
 * 
 * Example transformations:
 * "install" → "install setup configure installation"
 * "fix error" → "fix error solve problem troubleshoot debug"
 * "api" → "api rest endpoint integration"
 * 
 * Why this works:
 * More keywords = higher chance of matching relevant docs
 * Semantic search finds similar concepts
 * 
 * @param string $query Original user query
 * @return string Optimized query
 */
function rewriter_optimize_query($query) {
    // Clean and normalize
    $query = strtolower(trim($query));
    $query = preg_replace('/[^\w\s]/', ' ', $query);
    
    // Extract base keywords
    $keywords = rewriter_extract_keywords($query);
    
    // Expand with synonyms and related terms
    $expanded = rewriter_expand_query($keywords);
    
    // Combine original + expanded
    $optimized = $query . ' ' . $expanded;
    
    // Remove duplicates and extra spaces
    $words = array_unique(explode(' ', $optimized));
    $optimized = implode(' ', array_filter($words));
    
    return $optimized;
}

/**
 * Expands query with synonyms and related terms
 * 
 * Educational concept - Synonym expansion:
 * Different people use different words for same concept:
 * - "install" = "setup", "configure", "installation"
 * - "error" = "problem", "issue", "bug"
 * - "api" = "endpoint", "REST", "integration"
 * 
 * By expanding, we match docs written with ANY of these terms!
 * 
 * Limitations:
 * This is a simple keyword-based approach.
 * Production systems use:
 * - Thesaurus APIs (WordNet)
 * - LLM-based expansion
 * - Domain-specific ontologies
 * 
 * @param array $keywords Array of keywords to expand
 * @return string Expanded terms
 */
function rewriter_expand_query($keywords) {
    // Simple synonym dictionary
    // Educational: In production, use a proper thesaurus or LLM
    $synonyms = array(
        'install' => 'setup configure installation deploy',
        'error' => 'problem issue bug exception failure',
        'fix' => 'solve resolve repair troubleshoot debug',
        'api' => 'rest endpoint integration interface',
        'key' => 'token credential password authentication',
        'config' => 'configuration settings options parameters',
        'run' => 'execute launch start invoke',
        'create' => 'generate build make construct',
        'delete' => 'remove erase destroy drop',
        'update' => 'modify change edit revise',
    );
    
    $expanded = array();
    
    foreach ($keywords as $keyword) {
        if (isset($synonyms[$keyword])) {
            $expanded[] = $synonyms[$keyword];
        }
    }
    
    return implode(' ', $expanded);
}

/**
 * Extracts important keywords from query
 * 
 * Educational concept - Keyword extraction:
 * Not all words are equally important!
 * - Important: "install", "api", "error"
 * - Unimportant: "how", "to", "the", "a"
 * 
 * We filter out "stop words" (common but meaningless words)
 * and keep only content-bearing words.
 * 
 * Why this matters:
 * - Shorter queries = faster search
 * - Focus on important terms = better matches
 * - Remove noise = higher precision
 * 
 * @param string $query Query string
 * @return array Array of important keywords
 */
function rewriter_extract_keywords($query) {
    // Common stop words to ignore
    // Educational: These add no semantic value for retrieval
    $stop_words = array(
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for',
        'from', 'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on',
        'that', 'the', 'to', 'was', 'will', 'with', 'how', 'what',
        'when', 'where', 'who', 'why', 'i', 'you', 'we', 'they'
    );
    
    // Split into words
    $words = preg_split('/\s+/', strtolower($query));
    
    // Filter out stop words and short words
    $keywords = array();
    foreach ($words as $word) {
        $word = trim($word);
        // Keep words that are:
        // - Longer than 2 characters
        // - Not in stop words list
        if (strlen($word) > 2 && !in_array($word, $stop_words)) {
            $keywords[] = $word;
        }
    }
    
    return array_unique($keywords);
}
