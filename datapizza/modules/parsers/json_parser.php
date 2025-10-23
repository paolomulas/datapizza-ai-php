<?php

/**
 * 🍕 Datapizza-AI PHP - JSON Parser
 * 
 * Parses JSON documents and extracts text.
 * Converts structured data → flat text for embeddings.
 * 
 * Educational challenge:
 * JSON is structured (key-value pairs, nesting).
 * AI needs flat text. How do we convert?
 * 
 * Strategy: Recursively extract all string values
 */

/**
 * Parses JSON file and extracts text
 * 
 * Educational flow:
 * 1. Read JSON file
 * 2. Decode to PHP array
 * 3. Recursively extract all text
 * 4. Join into single string
 * 
 * @param string $filepath Path to JSON file
 * @return array ['text' => extracted, 'metadata' => info]
 * @throws Exception If file invalid or not valid JSON
 */
function parser_parse_json($filepath) {
    // Read file
    if (!file_exists($filepath)) {
        throw new Exception("File not found: $filepath");
    }
    
    $json_string = file_get_contents($filepath);
    
    // Parse JSON
    $data = json_decode($json_string, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }
    
    // Extract all text from structure
    $text = parser_json_extract_text($data);
    
    // Build metadata
    $metadata = array(
        'filename' => basename($filepath),
        'type' => 'json',
        'size_bytes' => filesize($filepath),
        'json_depth' => parser_json_max_depth($data)
    );
    
    return array(
        'text' => $text,
        'metadata' => $metadata
    );
}

/**
 * Recursively extracts text from JSON structure
 * 
 * Helper function (internal use).
 * Handles nested objects/arrays.
 * 
 * @param mixed $data JSON data
 * @return string Extracted text
 */
function parser_json_extract_text($data) {
    $texts = array();
    
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            // Add key if it's named (not numeric index)
            if (!is_numeric($key)) {
                $texts[] = $key;
            }
            
            // Recursively process value
            if (is_array($value)) {
                $texts[] = parser_json_extract_text($value);
            } elseif (is_string($value)) {
                $texts[] = $value;
            } elseif (is_numeric($value) || is_bool($value)) {
                $texts[] = (string)$value;
            }
        }
    } elseif (is_string($data)) {
        $texts[] = $data;
    }
    
    return implode(' ', array_filter($texts));
}

/**
 * Calculates max depth of JSON structure
 * 
 * Helper function for metadata.
 * 
 * @param mixed $data JSON data
 * @param int $depth Current depth
 * @return int Maximum depth
 */
function parser_json_max_depth($data, $depth = 0) {
    if (!is_array($data)) {
        return $depth;
    }
    
    $max = $depth;
    foreach ($data as $value) {
        if (is_array($value)) {
            $max = max($max, parser_json_max_depth($value, $depth + 1));
        }
    }
    
    return $max;
}
