<?php

/**
 * 🍕 Datapizza-AI PHP - Kimi/Moonshot AI Client
 * 
 * Client for Kimi (月之暗面 - Moonshot AI), a Chinese AI company
 * offering large context windows and competitive pricing.
 * 
 * Educational note:
 * Kimi uses OpenAI-compatible API format, demonstrating how
 * international providers adopt standardized interfaces.
 * This makes it easy to switch between providers globally.
 * 
 * Notable feature:
 * Kimi supports extremely large context windows (200K+ tokens),
 * making it useful for processing long documents on Raspberry Pi.
 */

require_once __DIR__ . '/dpz_call.php';

/**
 * Sends a simple completion request to Kimi
 * 
 * This is a convenience wrapper for single-turn completions.
 * Uses the standard Chat API format with a single user message.
 * 
 * @param string $prompt User's question or instruction
 * @param string $model Model to use (default: moonshot-v1-8k)
 * @param int $max_tokens Maximum tokens to generate
 * @param float $temperature Creativity level (0.0 = focused, 1.0 = creative)
 * @return string Kimi's response text
 */
function kimi_complete($prompt, $model = 'moonshot-v1-8k', $max_tokens = 150, $temperature = 0.7) {
    
    $provider = "kimi";
    $endpoint = "v1/chat/completions";
    $base_url = "https://api.moonshot.cn";
    
    // Build payload in OpenAI-compatible format
    $payload = [
        "model" => $model,
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ],
        "max_tokens" => $max_tokens,
        "temperature" => $temperature
    ];

    try {
        $result = dpz_call($provider, $endpoint, $payload, $base_url);
        
        // Extract text from OpenAI-compatible response
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        } else {
            throw new Exception("Unexpected response format from Kimi");
        }
        
    } catch (Exception $e) {
        return "[ERROR Kimi: " . $e->getMessage() . "]";
    }
}

/**
 * Sends a multi-turn conversation to Kimi
 * 
 * This function accepts a full messages array, allowing you to
 * maintain conversation context across multiple turns.
 * 
 * Educational concept:
 * Multi-turn conversations help the AI understand context better.
 * Format: [{"role": "user", "content": "..."}, {"role": "assistant", "content": "..."}, ...]
 * 
 * @param array $messages Array of message objects with 'role' and 'content'
 * @param string $model Model to use (supports k2-0905-preview for latest)
 * @return string Kimi's response text
 */
function kimi_chat($messages, $model = 'kimi-k2-0905-preview') {
    
    $provider = "kimi";
    $endpoint = "v1/chat/completions";
    $base_url = "https://api.moonshot.ai";  // Note: .ai domain, not .cn
    
    $payload = [
        "model" => $model,
        "messages" => $messages,
        "temperature" => 0.6  // Slightly lower for more consistent conversations
    ];

    try {
        $result = dpz_call($provider, $endpoint, $payload, $base_url);
        
        // Return response content, or empty string if not found
        return trim($result['choices'][0]['message']['content'] ?? '');
        
    } catch (Exception $e) {
        return "[ERROR Kimi: " . $e->getMessage() . "]";
    }
}
