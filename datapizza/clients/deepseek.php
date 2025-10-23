<?php

/**
 * 🍕 Datapizza-AI PHP - DeepSeek Client
 * 
 * DeepSeek is a Chinese AI company offering OpenAI-compatible APIs
 * at very competitive prices. Great for learning and experimentation.
 * 
 * Educational note:
 * DeepSeek's API is OpenAI-compatible, showing how standardization
 * helps - we can reuse the same request/response patterns.
 */

require_once 'dpz_call.php';

/**
 * Sends a completion request to DeepSeek
 * 
 * DeepSeek uses OpenAI-compatible format, making it easy to switch
 * between providers. This is the power of API standardization.
 * 
 * @param string $prompt User's question or instruction
 * @param int $max_tokens Maximum length of response
 * @param float $temperature Creativity level (0.0 = focused, 1.0 = creative)
 * @return string DeepSeek's response text
 */
function deepseek_complete($prompt, $max_tokens = 150, $temperature = 0.7) {
    
    $provider = 'deepseek';
    $endpoint = 'chat/completions';  // Note: no 'v1/' prefix
    $base_url = 'https://api.deepseek.com';
    
    // Payload is identical to OpenAI format
    $payload = [
        'model' => 'deepseek-chat',  // Their main conversational model
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => $max_tokens,
        'temperature' => $temperature
    ];

    try {
        $result = dpz_call($provider, $endpoint, $payload, $base_url);
        
        // Response format is OpenAI-compatible
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        } else {
            throw new Exception("Unexpected response format: " . json_encode($result));
        }
        
    } catch (Exception $e) {
        return "[ERROR DeepSeek: " . $e->getMessage() . "]";
    }
}
