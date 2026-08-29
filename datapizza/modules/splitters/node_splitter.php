<?php

/**
 * 🍕 Datapizza-AI PHP - Node Splitter
 * 
 * Splits documents into smaller chunks (nodes) for RAG processing.
 * This is a CRITICAL component for Retrieval-Augmented Generation!
 * 
 * Educational concept - Why splitting matters:
 * 
 * Problem: You can't embed an entire 100-page PDF as one vector.
 * - Embeddings have token limits (8K tokens for text-embedding-ada-002)
 * - Large texts lose granularity (can't pinpoint specific info)
 * - Search becomes less precise
 * 
 * Solution: Split into chunks!
 * - Each chunk = one embedding = one searchable unit
 * - Smaller chunks = more precise retrieval
 * - But too small = loses context!
 * 
 * Example:
 * Document: "Introduction to AI... [5000 words]"
 * After splitting: ["Introduction to AI (chunk 1)", "Deep Learning (chunk 2)", ...]
 * Each chunk embedded separately → better semantic search
 * 
 * Chunk size guidelines:
 * - 200-500 tokens: Good for Q&A, precise retrieval
 * - 500-1000 tokens: Good for context-rich retrieval
 * - 1000+ tokens: Loses precision, use only if needed
 */

/**
 * Splits text into chunks with overlap
 * 
 * Educational flow:
 * 1. Calculate chunk boundaries (based on max_chunk_size)
 * 2. Find "smart" boundaries (sentence/paragraph ends)
 * 3. Add overlap between chunks (preserves context)
 * 4. Return array of text chunks
 * 
 * Why overlap?
 * Imagine splitting mid-sentence: "The capital of France is..."
 * Next chunk: "...Paris" - loses context!
 * With overlap, both chunks have "...France is Paris..."
 * 
 * @param string $text Full document text
 * @param int $max_chunk_size Maximum chunk size in characters (default: 1000)
 * @param int $overlap Overlap between chunks in characters (default: 200)
 * @return array Array of text chunks
 */
function splitter_split($text, $max_chunk_size = 1000, $overlap = 200) {
    $max_chunk_size = (int)$max_chunk_size;
    $overlap = (int)$overlap;

    if ($max_chunk_size <= 0) {
        throw new InvalidArgumentException('max_chunk_size must be greater than zero');
    }

    if ($overlap < 0 || $overlap >= $max_chunk_size) {
        throw new InvalidArgumentException('overlap must be zero or greater and smaller than max_chunk_size');
    }

    // Handle edge case: text shorter than chunk size
    if (strlen($text) <= $max_chunk_size) {
        return array($text);
    }
    
    $chunks = array();
    $start = 0;
    $text_length = strlen($text);
    
    while ($start < $text_length) {
        // Calculate end position for this chunk
        $end = min($start + $max_chunk_size, $text_length);
        
        // Find smart boundary (sentence or paragraph end)
        // This prevents splitting mid-sentence
        if ($end < $text_length) {
            $boundary = splitter_find_boundary($text, $start, $end);
            if ($boundary !== false) {
                $end = $boundary;
            }
        }
        
        // Extract chunk
        $chunk = trim(substr($text, $start, $end - $start));
        if ($chunk !== '') {
            $chunks[] = $chunk;
        }

        if ($end >= $text_length) {
            break;
        }
        
        // Move to next chunk with overlap
        // Overlap ensures context continuity between chunks
        $next_start = $end - $overlap;

        // A smart boundary can be closer to the current start than the requested
        // overlap. In that case, keep the boundary and drop overlap for this step
        // rather than moving backwards or repeating the same slice forever.
        if ($next_start <= $start) {
            $next_start = $end;
        }

        $start = $next_start;
    }
    
    return $chunks;
}

/**
 * Finds "smart" boundary for chunk splitting
 * 
 * Educational concept - Smart boundaries:
 * Don't split mid-word or mid-sentence!
 * Look for natural break points:
 * 1. Paragraph breaks (\n\n)
 * 2. Sentence ends (. ! ?)
 * 3. Comma/semicolon (if nothing else)
 * 
 * This preserves semantic meaning in each chunk.
 * 
 * @param string $text Full text
 * @param int $start Start position
 * @param int $end Proposed end position
 * @return int|false Position of smart boundary, or false if not found
 */
function splitter_find_boundary($text, $start, $end) {
    // Look backwards from end for good split point
    $search_range = min(200, $end - $start);
    $search_start = max($start, $end - $search_range);
    $search_text = substr($text, $search_start, $end - $search_start);
    
    // Priority 1: Paragraph break (double newline)
    $pos = strrpos($search_text, "\n\n");
    if ($pos !== false) {
        return $search_start + $pos + 2;
    }
    
    // Priority 2: Sentence end (. ! ?)
    $sentence_endings = array('. ', '! ', '? ', ".\n", "!\n", "?\n");
    $best_pos = false;
    
    foreach ($sentence_endings as $ending) {
        $pos = strrpos($search_text, $ending);
        if ($pos !== false && ($best_pos === false || $pos > $best_pos)) {
            $best_pos = $pos + strlen($ending);
        }
    }
    
    if ($best_pos !== false) {
        return $search_start + $best_pos;
    }
    
    // Priority 3: Comma or semicolon
    $pos = strrpos($search_text, ', ');
    if ($pos !== false) {
        return $search_start + $pos + 2;
    }
    
    $pos = strrpos($search_text, '; ');
    if ($pos !== false) {
        return $search_start + $pos + 2;
    }
    
    // Priority 4: Any whitespace
    $pos = strrpos($search_text, ' ');
    if ($pos !== false) {
        return $search_start + $pos + 1;
    }
    
    // No good boundary found - split at hard limit
    return false;
}

/**
 * Splits multiple documents in batch
 * 
 * Convenience function for processing many documents at once.
 * Useful when building a vectorstore from a document collection.
 * 
 * Educational use case:
 * You have 100 PDFs to index for RAG.
 * Instead of manually splitting each:
 * $all_chunks = splitter_split_batch($documents);
 * 
 * Returns flat array of all chunks from all documents.
 * 
 * @param array $documents Array of document texts
 * @param int $max_chunk_size Max chunk size
 * @param int $overlap Overlap size
 * @return array Flat array of all chunks
 */
function splitter_split_batch($documents, $max_chunk_size = 1000, $overlap = 200) {
    $all_chunks = array();
    
    foreach ($documents as $doc) {
        $chunks = splitter_split($doc, $max_chunk_size, $overlap);
        $all_chunks = array_merge($all_chunks, $chunks);
    }
    
    return $all_chunks;
}
