<?php

/**
 * 🍕 Datapizza-AI PHP - CSV Parser
 * 
 * Parses CSV/TSV files and converts to text.
 * Converts tabular data → structured text for embeddings.
 * 
 * Educational challenge:
 * CSV is rows/columns. How to convert to text?
 * 
 * Strategy: Format as "header: value" pairs per row
 */

/**
 * Parses CSV file and converts to text
 * 
 * Educational flow:
 * 1. Read CSV line by line (memory efficient)
 * 2. First row = headers
 * 3. Each row = "header: value" pairs
 * 4. Join rows with newlines
 * 
 * @param string $filepath Path to CSV file
 * @param string $delimiter Column separator (',' for CSV, "\t" for TSV)
 * @return array ['text' => formatted, 'metadata' => info]
 * @throws Exception If file invalid
 */
function parser_parse_csv($filepath, $delimiter = ',') {
    // Validate file
    if (!file_exists($filepath)) {
        throw new Exception("File not found: $filepath");
    }
    
    // Open file
    $handle = fopen($filepath, 'r');
    if ($handle === false) {
        throw new Exception("Could not open file: $filepath");
    }
    
    // Read headers
    $headers = fgetcsv($handle, 0, $delimiter);
    
    if ($headers === false) {
        fclose($handle);
        throw new Exception("Empty CSV file");
    }
    
    // Process rows
    $text_rows = array();
    $row_count = 0;
    
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        // Format as "header: value" pairs
        $row_parts = array();
        
        foreach ($headers as $idx => $header) {
            $value = isset($row[$idx]) ? $row[$idx] : '';
            $row_parts[] = "$header: $value";
        }
        
        $text_rows[] = implode(', ', $row_parts);
        $row_count++;
    }
    
    fclose($handle);
    
    // Build metadata
    $metadata = array(
        'filename' => basename($filepath),
        'type' => 'csv',
        'size_bytes' => filesize($filepath),
        'column_count' => count($headers),
        'row_count' => $row_count,
        'headers' => $headers
    );
    
    return array(
        'text' => implode("\n", $text_rows),
        'metadata' => $metadata
    );
}
