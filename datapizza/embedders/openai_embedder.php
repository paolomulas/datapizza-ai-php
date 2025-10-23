<?php

/**
 * 🍕 Datapizza-AI PHP - OpenAI Embedder
 * 
 * Implementation of text embedding using OpenAI's Embeddings API.
 * Uses text-embedding-3-small by default - a good balance of
 * quality, speed, and cost for educational projects.
 * 
 * Educational concepts demonstrated:
 * - API-based embedding generation (no local model needed)
 * - Batch API optimization for multiple texts
 * - Configurable dimensions for space/quality tradeoff
 * - Error handling for API failures
 * 
 * Why OpenAI embeddings:
 * - High quality semantic representations
 * - Fast inference (good for Raspberry Pi)
 * - Affordable pricing (~$0.00002 per 1K tokens)
 * - No local GPU or model downloads needed
 * 
 * Learn more: https://platform.openai.com/docs/guides/embeddings
 */

require_once __DIR__ . '/../clients/dpz_call.php';
require_once __DIR__ . '/base_embedder.php';

class OpenAIEmbedder extends BaseEmbedder {
    
    private $model;       // Which OpenAI embedding model to use
    private $dimensions;  // Output vector dimensions (optional reduction)
    
    /**
     * Constructor - Sets up OpenAI embedding configuration
     * 
     * @param string $model OpenAI model name (default: text-embedding-3-small)
     * @param int|null $dimensions Optional dimension reduction (512, 256, etc.)
     *                            null = use model's native dimensions (1536)
     */
    public function __construct($model = 'text-embedding-3-small', $dimensions = null) {
        $this->model = $model;
        $this->dimensions = $dimensions;
    }
    
    /**
     * Generates embedding for a single text
     * 
     * Makes a synchronous API call to OpenAI to convert text into
     * a numerical vector. The vector captures semantic meaning so
     * similar texts have similar vectors.
     * 
     * Educational note:
     * This is a "network call" - the actual embedding computation
     * happens on OpenAI's servers, not your Raspberry Pi. This is
     * why we can run modern AI on 2011 hardware!
     * 
     * @param string $text Text to embed (max ~8000 tokens)
     * @return array Float vector of length 1536 (or configured dimensions)
     * @throws Exception If API call fails or returns unexpected format
     */
    public function embed($text) {
        $provider = "openai";
        $endpoint = "v1/embeddings";
        
        // Build API payload
        $payload = [
            "model" => $this->model,
            "input" => $text  // Single string for single embedding
        ];
        
        // Add dimensions parameter if configured
        // This uses OpenAI's "Matryoshka" dimension reduction
        if ($this->dimensions !== null) {
            $payload["dimensions"] = $this->dimensions;
        }
        
        try {
            // Make API call through our universal client
            $result = dpz_call($provider, $endpoint, $payload);
            
            // Extract embedding vector from response
            // Response format: { data: [{ embedding: [0.1, 0.2, ...] }] }
            if (isset($result['data'][0]['embedding'])) {
                return $result['data'][0]['embedding'];
            } else {
                throw new Exception("Unexpected embedding response: " . json_encode($result));
            }
            
        } catch (Exception $e) {
            throw new Exception("Error generating embedding: " . $e->getMessage());
        }
    }
    
    /**
     * Generates embeddings for multiple texts efficiently (batch API)
     * 
     * This overrides the base class implementation to use OpenAI's
     * native batch embedding API. Much more efficient than looping!
     * 
     * Educational comparison:
     * - Base class: N API calls (one per text)
     * - This method: 1 API call for all texts
     * - On Raspberry Pi: Huge latency and bandwidth savings
     * 
     * Batch limits:
     * - Max 2048 texts per batch
     * - Max ~8000 tokens per text
     * - If you exceed limits, split into multiple batches
     * 
     * @param array $texts Array of text strings to embed
     * @return array Array of embedding vectors (same order as input)
     * @throws Exception If API call fails
     */
    public function embed_batch($texts) {
        $provider = "openai";
        $endpoint = "v1/embeddings";
        
        // Build batch payload
        // OpenAI accepts array of strings for batch processing
        $payload = [
            "model" => $this->model,
            "input" => $texts  // Array of strings for batch
        ];
        
        if ($this->dimensions !== null) {
            $payload["dimensions"] = $this->dimensions;
        }
        
        try {
            $result = dpz_call($provider, $endpoint, $payload);
            
            // Extract all embeddings from batch response
            // Response: { data: [{ embedding: [...] }, { embedding: [...] }, ...] }
            if (isset($result['data']) && is_array($result['data'])) {
                $embeddings = [];
                foreach ($result['data'] as $item) {
                    if (isset($item['embedding'])) {
                        $embeddings[] = $item['embedding'];
                    }
                }
                return $embeddings;
            } else {
                throw new Exception("Unexpected batch embedding response: " . json_encode($result));
            }
            
        } catch (Exception $e) {
            throw new Exception("Error generating batch embeddings: " . $e->getMessage());
        }
    }
    
    /**
     * Returns embedding vector dimensions
     * 
     * If custom dimensions were specified in constructor, returns that.
     * Otherwise returns the model's native dimensions.
     * 
     * Educational note:
     * text-embedding-3-small natively produces 1536 dimensions.
     * You can reduce this to 512, 256, or any value using the
     * dimensions parameter. Quality degrades gracefully with reduction.
     * 
     * @return int Number of dimensions in output vectors
     */
    public function get_dimensions() {
        // Return configured dimensions, or model default
        if ($this->dimensions !== null) {
            return $this->dimensions;
        }
        
        // Default dimensions for each model
        // These are the native dimensions when not reduced
        $default_dimensions = [
            'text-embedding-3-small' => 1536,
            'text-embedding-3-large' => 3072,
            'text-embedding-ada-002' => 1536  // Legacy model
        ];
        
        return $default_dimensions[$this->model] ?? 1536;
    }
}
