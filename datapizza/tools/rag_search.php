<?php

/**
 * 🍕 Datapizza-AI PHP - RAG Search Tool
 * 
 * Retrieval-Augmented Generation (RAG) search tool.
 * Searches internal knowledge base using semantic similarity.
 * 
 * Educational concepts:
 * - RAG pattern (Retrieval-Augmented Generation)
 * - Semantic search (meaning-based, not keyword)
 * - Vector similarity (cosine distance)
 * - Internal knowledge base vs external search
 * 
 * What is RAG?
 * RAG = Retrieval + Generation
 * 1. Retrieval: Find relevant documents from knowledge base
 * 2. Generation: LLM generates answer using retrieved context
 * 
 * Why RAG matters:
 * - Extends LLM knowledge beyond training data
 * - Provides source attribution (which doc was used)
 * - Updates without retraining (just add docs to vectorstore)
 * - More accurate than pure LLM generation
 * 
 * Educational example:
 * Question: "How do I configure the API key?"
 * 1. Tool embeds question into vector
 * 2. Searches vectorstore for similar doc vectors
 * 3. Returns top 3 most similar docs
 * 4. AI uses docs to answer question accurately
 */

require_once __DIR__ . '/base_tool.php';

class RAGSearchTool extends BaseTool {
    
    private $vectorstore;  // Vector database for document storage
    private $embedder;     // Embedding generator for queries
    
    /**
     * Constructor - Injects dependencies
     * 
     * Educational pattern - Dependency Injection:
     * We don't create vectorstore/embedder here - they're passed in.
     * This makes the tool flexible and testable:
     * - Can swap different vectorstores (file, database, cloud)
     * - Can use different embedders (OpenAI, local models)
     * - Easy to mock for testing
     * 
     * @param object $vectorstore Vector store instance
     * @param object $embedder Embedder instance
     */
    public function __construct($vectorstore, $embedder) {
        $this->name = "rag_search";
        $this->description = "Searches relevant information in the internal knowledge base using semantic search. Use this tool when the user asks questions about topics that might be in the documentation.";
        $this->vectorstore = $vectorstore;
        $this->embedder = $embedder;
    }
    
    /**
     * Executes semantic search on internal knowledge base
     * 
     * Educational flow:
     * 1. Embed query text into vector (e.g., 1536 dimensions)
     * 2. Calculate cosine similarity with all docs in vectorstore
     * 3. Return top K most similar documents
     * 4. Include similarity score (0-100% relevance)
     * 
     * Why semantic search > keyword search:
     * Keyword: "car" matches only "car"
     * Semantic: "car" also matches "automobile", "vehicle", "sedan"
     * 
     * The embeddings capture meaning, not just exact words.
     * 
     * @param array $params Must contain 'query', optional 'top_k'
     * @return string Formatted relevant documents
     */
    public function execute($params = []) {
        if (!isset($params['query'])) {
            return "Error: parameter 'query' required";
        }
        
        $query = $params['query'];
        $top_k = $params['top_k'] ?? 3;  // Default: return top 3 results
        
        // Step 1: Generate embedding for query
        // This converts text like "How to install?" into a 1536-dim vector
        $query_embedding = $this->embedder->embed($query);
        
        // Step 2: Search vectorstore for similar documents
        // Vectorstore calculates cosine similarity with all stored docs
        $results = $this->vectorstore->search($query_embedding, $top_k);
        
        // Step 3: Format results for AI consumption
        if (empty($results)) {
            return "No relevant documents found in knowledge base for: '$query'";
        }
        
        $output = "Relevant documents found (ordered by relevance):\n\n";
        
        foreach ($results as $i => $result) {
            // Convert similarity score to percentage
            // Cosine similarity ranges from -1 to 1, we convert to 0-100%
            $score = round($result['score'] * 100, 1);
            
            $output .= "Document " . ($i + 1) . " (Relevance: {$score}%):\n";
            $output .= $result['text'] . "\n\n";
        }
        
        return $output;
    }
    
    /**
     * Returns parameter schema for AI
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query to find relevant documents'
                ],
                'top_k' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of documents to return (default: 3)'
                ]
            ],
            'required' => ['query']
        ];
    }
}
