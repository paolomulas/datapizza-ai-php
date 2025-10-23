<?php

/**
 * 🍕 Datapizza-AI PHP - Anthropic Claude Client
 * 
 * Wrapper for Anthropic's Claude API.
 * Claude uses a slightly different format than OpenAI - this demonstrates
 * how to adapt to provider-specific requirements.
 * 
 * Educational differences from OpenAI:
 * - Uses 'max_tokens' at root level (not 'max_completion_tokens')
 * - Response structure: content[0].text instead of choices[0].message.content
 * - Requires anthropic-version header (handled in dpz_call.php)
 */

require_once 'dpz_call.php';

/**
 * Sends a completion request to Anthropic Claude
 * 
 * Claude is known for longer context windows and strong reasoning.
 * We use the latest Sonnet model which balances speed and intelligence.
 * 
 * @param string $prompt User's question or instruction
 * @param int $max_tokens Maximum length of response
 * @param float $temperature Creativity level (0.0 = focused, 1.0 = creative)
 * @return string Claude's response text
 */
function anthropic_complete($prompt, $max_tokens = 4096, $temperature = 0.7) {
    
    $provider = 'anthropic';
    $endpoint = 'v1/messages';
    $base_url = 'https://api.anthropic.com';
    
    // Build payload in Anthropic's format
    // Note: different structure than OpenAI
    $payload = [
        'model' => 'claude-3-5-sonnet-20241022',  // Latest Sonnet model
        'max_tokens' => $max_tokens,  // At root level, not in generation_config
        'temperature' => $temperature,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    try {
        // Make the API call
        $result = dpz_call($provider, $endpoint, $payload, $base_url);
        
        // Extract text from Claude's response structure
        // Response format: { content: [{ text: "..." }] }
        if (isset($result['content'][0]['text'])) {
            return trim($result['content'][0]['text']);
        } else {
            throw new Exception("Unexpected response format: " . json_encode($result));
        }
        
    } catch (Exception $e) {
        return "[ERROR Anthropic: " . $e->getMessage() . "]";
    }
}
