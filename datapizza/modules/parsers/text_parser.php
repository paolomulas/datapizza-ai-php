<?php

/**
 * 🍕 Datapizza-AI PHP - Text Parser
 * 
 * Parses plain text files (.txt, .md, .log).
 * This is the simplest parser - just read the file!
 * 
 * Educational note:
 * Plain text is already AI-ready. No conversion needed!
 */

/**
 * Parses plain text file
 * 
 * Educational flow:
 * 1. Check file exists
 * 2. Read entire contents
 * 3. Return with metadata
 * 
 * Markdown handling:
 * We keep Markdown formatting (**, ##, etc.) because:
 * - Embeddings can learn from structure
 * - Headings provide useful context
 * - Formatting conveys meaning
 * 
 * @param string $filepath Path to text file
 * @return array ['text' => content, 'metadata' => info]
 * @throws Exception If file doesn't exist
 */
function parser_parse_text($filepath) {
    // Validate file exists
    if (!file_exists($filepath)) {
        throw new Exception("File not found: $filepath");
    }
    
    // Read entire file as UTF-8 text
    $content = file_get_contents($filepath);
    
    if ($content === false) {
        throw new Exception("Could not read file: $filepath");
    }
    
    // Build metadata
    $metadata = array(
        'filename' => basename($filepath),
        'type' => 'text',
        'size_bytes' => filesize($filepath),
        'line_count' => substr_count($content, "\n") + 1,
        'word_count' => str_word_count($content),
        'char_count' => strlen($content)
    );
    
    // Return standard format
    return array(
        'text' => $content,
        'metadata' => $metadata
    );
}
