<?php

/**
 * 🍕 Datapizza-AI PHP - Google Gemini Client
 * 
 * Google's Gemini uses a different API structure than other providers.
 * This demonstrates handling provider-specific formats while maintaining
 * a simple interface.
 * 
 * Educational differences from OpenAI:
 * - Uses 'contents' array with 'parts' sub-structure
 * - Parameters go in 'generationConfig' object
 * - Response structure: candidates[0].content.parts[0].text
 */

require_once 'dpz_call.php';

/**
 * Sends a completion request to Google Gemini
 * 
 * Gemini offers fast inference and good multimodal capabilities.
 * We use the Flash model which is optimized for speed.
 * 
 * @param string $prompt User's question or instruction
 * @param int $max_tokens Maximum length of response
 * @param float $temperature Creativity level (0.0 = focused, 1.0 = creative)
 * @return string Gemini's response text
 */
function google_complete($prompt, $max_tokens = 150, $temperature = 0.7) {
    
    $provider = 'google';
    $endpoint = 'v1beta/models/gemini-1.5-flash:generateContent';
    
    // Build payload in Google's format (different from OpenAI)
    $payload = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'maxOutputTokens' => $max_tokens,
            'temperature' => $temperature
        ]
    ];

    try {
        $result = dpz_call($provider, $endpoint, $payload);
        
        // Extract text from Google's nested response structure
        // Response format: { candidates: [{ content: { parts: [{ text: "..." }] } }] }
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($result['candidates'][0]['content']['parts'][0]['text']);
        } else {
            throw new Exception("Unexpected response format: " . json_encode($result));
        }
        
    } catch (Exception $e) {
        return "[ERROR Google: " . $e->getMessage() . "]";
    }
}
