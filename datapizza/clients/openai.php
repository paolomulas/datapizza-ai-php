<?php

/**
 * 🍕 Datapizza-AI PHP - OpenAI Client
 * 
 * Client for OpenAI's Chat Completions API using gpt-4o-mini.
 * This model represents modern AI architecture - fast, cost-efficient,
 * and more capable than GPT-3.5 while being perfect for learning.
 * 
 * Educational note:
 * We use the Chat API (/v1/chat/completions) instead of the legacy
 * Completions API. This is the standard format that most modern AI
 * services have adopted, making your skills transferable.
 * 
 * Why gpt-4o-mini?
 * - Faster than GPT-4 (better for Raspberry Pi)
 * - Cheaper than GPT-3.5 but smarter
 * - Uses model distillation (student learns from GPT-4o teacher)
 * - Perfect balance for educational projects
 */

require_once __DIR__ . '/dpz_call.php';

/**
 * Sends a chat completion request to OpenAI
 * 
 * This uses the modern Chat API format with a messages array.
 * Even though we're sending a single prompt, this structure
 * allows easy extension to multi-turn conversations later.
 * 
 * @param string $prompt The user's question or instruction
 * @param int $max_tokens Maximum tokens to generate (default: 150)
 * @param float $temperature Creativity 0.0-2.0 (default: 0.7)
 *                          0.0 = deterministic, 2.0 = very creative
 * @return string Generated response text
 */
function openai_complete($prompt, $max_tokens = 150, $temperature = 0.7) {
    
    $provider = "openai";
    $endpoint = "v1/chat/completions";  // Modern Chat API

    // Build payload in Chat API format
    // This is the de-facto standard format across AI providers
    $payload = [
        "model" => "gpt-4o-mini",  // Fast, cost-efficient, modern model
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ],
        "max_tokens" => $max_tokens,
        "temperature" => $temperature
    ];

    try {
        // Make the API call
        $result = dpz_call($provider, $endpoint, $payload);
        
        // Extract text from Chat API response format
        // Response: { choices: [{ message: { content: "..." } }] }
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        } else {
            throw new Exception("Unexpected response format: " . json_encode($result));
        }
        
    } catch (Exception $e) {
        return "[ERROR OpenAI: " . $e->getMessage() . "]";
    }
}
