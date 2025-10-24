<?php

/**
 * 🍕 Datapizza-AI PHP - Document Ingestion Pipeline
 * 
 * Orchestrates the complete RAG ingestion workflow:
 * Parse → Split → Embed → Store
 * 
 * Educational concept - Why pipelines?
 * 
 * RAG requires multiple sequential steps to prepare documents:
 * 1. Parse: Extract text from file (PDF, JSON, CSV, etc.)
 * 2. Split: Divide into manageable chunks
 * 3. Embed: Convert text → numerical vectors
 * 4. Store: Save in vectorstore for retrieval
 * 
 * A pipeline automates this! Instead of manually calling 4 functions,
 * one function call does everything:
 * 
 * pipeline_ingest($filepath, $embedder, $vectorstore);
 * 
 * Benefits:
 * - Consistency: Same process every time
 * - Error handling: Centralized error management
 * - Batch processing: Ingest 100 documents with one call
 * - Maintainability: Change pipeline logic in one place
 */




/**
 * Simplified ingestion for text strings (not files)
 * 
 * @param array $docs Array of text strings
 * @param object $embedder Embedder instance
 * @param object $vectorstore VectorStore instance
 * @param int $chunk_size Unused (kept for compatibility)
 * @param int $chunk_overlap Unused (kept for compatibility)
 * @param array $metadata Metadata to attach to all docs
 * @return int Number of documents ingested
 */
function pipeline_ingest_texts($docs, $embedder, $vectorstore, $chunk_size = 500, $chunk_overlap = 50, $metadata = array()) {
    $count = 0;
    
    foreach ($docs as $doc) {
        // Embed the document
        $embedding = $embedder->embed($doc);
        
        // Store in vectorstore
        $vectorstore->add_document($doc, $embedding, $metadata);
        
        $count++;
        
        echo "   ✓ Ingested: " . substr($doc, 0, 60) . "...\n";
    }
    
    echo "\n";
    return $count;
}





/**
 * Ingests multiple documents into vectorstore
 * 
 * Educational flow - Batch ingestion:
 * 1. Loop through all file paths
 * 2. For each file, call pipeline_ingest_single()
 * 3. Collect results (success/failure)
 * 4. Return summary report
 * 
 * Use case:
 * You have 100 markdown files to index for RAG.
 * Instead of processing each manually:
 * 
 * $files = glob('docs/*.md');
 * $results = pipeline_ingest($files, $embedder, $vectorstore);
 * 
 * Now all 100 documents are searchable!
 * 
 * @param array $filepaths Array of file paths to ingest
 * @param object $embedder Embedder instance (OpenAIEmbedder, etc.)
 * @param object $vectorstore VectorStore instance
 * @param array $options Optional parameters (chunk_size, overlap, etc.)
 * @return array Ingestion results ['success' => N, 'failed' => M, 'details' => [...]]
 */
function pipeline_ingest($filepaths, $embedder, $vectorstore, $options = array()) {
    // Default options
    $chunk_size = isset($options['chunk_size']) ? $options['chunk_size'] : 1000;
    $overlap = isset($options['overlap']) ? $options['overlap'] : 200;
    
    $results = array(
        'success' => 0,
        'failed' => 0,
        'details' => array()
    );
    
    // Process each file
    foreach ($filepaths as $filepath) {
        try {
            // Ingest single file
            $file_result = pipeline_ingest_single(
                $filepath,
                $embedder,
                $vectorstore,
                $chunk_size,
                $overlap
            );
            
            $results['success']++;
            $results['details'][] = array(
                'file' => basename($filepath),
                'status' => 'success',
                'chunks' => $file_result['chunk_count']
            );
            
        } catch (Exception $e) {
            $results['failed']++;
            $results['details'][] = array(
                'file' => basename($filepath),
                'status' => 'failed',
                'error' => $e->getMessage()
            );
        }
    }
    
    return $results;
}

/**
 * Ingests a single document (complete pipeline)
 * 
 * Educational flow - Complete RAG pipeline:
 * 
 * STEP 1: PARSE
 * Extract text from file based on extension
 * - .txt/.md → parser_parse_text()
 * - .json → parser_parse_json()
 * - .csv → parser_parse_csv()
 * 
 * STEP 2: SPLIT
 * Divide text into chunks (overlapping)
 * - Why? Embeddings have token limits
 * - Chunks = searchable units
 * 
 * STEP 3: EMBED
 * Convert each chunk to numerical vector
 * - Example: "Hello world" → [0.123, -0.456, 0.789, ...]
 * - Semantic meaning encoded in numbers
 * 
 * STEP 4: STORE
 * Save chunks + embeddings in vectorstore
 * - Now searchable via similarity
 * - Ready for RAG retrieval!
 * 
 * @param string $filepath Path to document
 * @param object $embedder Embedder instance
 * @param object $vectorstore VectorStore instance
 * @param int $chunk_size Max chunk size
 * @param int $overlap Overlap between chunks
 * @return array Result with chunk count
 * @throws Exception If any step fails
 */
function pipeline_ingest_single($filepath, $embedder, $vectorstore, $chunk_size = 1000, $overlap = 200) {
    // STEP 1: PARSE - Extract text from file
    $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    
    // Route to appropriate parser based on file type
    switch ($extension) {
        case 'txt':
        case 'md':
            require_once __DIR__ . '/../modules/parsers/text_parser.php';
            $parsed = parser_parse_text($filepath);
            break;
        case 'json':
            require_once __DIR__ . '/../modules/parsers/json_parser.php';
            $parsed = parser_parse_json($filepath);
            break;
        case 'csv':
            require_once __DIR__ . '/../modules/parsers/csv_parser.php';
            $parsed = parser_parse_csv($filepath);
            break;
        default:
            throw new Exception("Unsupported file type: $extension");
    }
    
    $text = $parsed['text'];
    $metadata = $parsed['metadata'];
    
    // STEP 2: SPLIT - Divide into chunks
    require_once __DIR__ . '/../modules/splitters/node_splitter.php';
    $chunks = splitter_split($text, $chunk_size, $overlap);
    
    // STEP 3 & 4: EMBED + STORE - For each chunk
    foreach ($chunks as $i => $chunk) {
        // Generate embedding for chunk
        $embedding = $embedder->embed($chunk);
        
        // Prepare chunk metadata
        $chunk_metadata = array_merge($metadata, array(
            'chunk_index' => $i,
            'total_chunks' => count($chunks),
            'source_file' => basename($filepath)
        ));
        
        // Store in vectorstore
        $vectorstore->add_document($chunk, $embedding, $chunk_metadata);
    }
    
    return array(
        'chunk_count' => count($chunks),
        'metadata' => $metadata
    );
}
