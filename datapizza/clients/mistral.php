<?php

/**
 * 🍕 Datapizza-AI PHP - Mistral AI Client
 * 
 * Client for Mistral AI, a French AI company known for
 * open-weight models and European data sovereignty.
 * 
 * Educational note:
 * Mistral demonstrates that AI innovation isn't limited to
 * US companies. Their models compete with GPT-4 while being
 * more transparent about training and architecture.
 * 
 * Why Mistral for learning:
 * - Clear documentation of model capabilities
 * - Competitive pricing for students/makers
 * - OpenAI-compatible API (easy to learn)
 */

require_once __DIR__ . '/dpz_call.php';

/**
 * Sends a completion request to Mistral AI
 * 
 * Mistral uses the standard Chat Completions format,
 * making it interchangeable with OpenAI, DeepSeek, etc.
 * 
 * @param string $prompt User's question or instruction
 * @param int $max_tokens Maximum tokens to generate (default: 150)
 * @param float $temperature Creativity level (default: 0.7)
 * @return string Mistral's response text
 */
function mistral_complete($prompt, $max_tokens = 150, $temperature = 0.7) {
    
    $provider = "mistral";
    $endpoint = "v1/chat/completions";
    
    // Build payload in standard Chat API format
    $payload = [
        "model" => "mistral-small-latest",  // Fast, cost-efficient model
        "max_tokens" => $max_tokens,
        "temperature" => $temperature,
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
    ];

    try {
        // Make the API call
        $result = dpz_call($provider, $endpoint, $payload);
        
        // Extract text from standard Chat API response
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        } else {
            throw new Exception("Unexpected response format: " . json_encode($result));
        }
        
    } catch (Exception $e) {
        return "[ERROR Mistral: " . $e->getMessage() . "]";
    }
}
