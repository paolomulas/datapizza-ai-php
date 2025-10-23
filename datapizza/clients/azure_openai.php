<?php

/**
 * 🍕 Datapizza-AI PHP - Azure OpenAI Client
 * 
 * Azure OpenAI is Microsoft's managed OpenAI service.
 * Uses same models as OpenAI but requires different authentication.
 * 
 * Educational note:
 * This shows how enterprises often wrap third-party APIs for
 * compliance, billing, or integration purposes.
 */

require_once 'dpz_call.php';

/**
 * Sends a completion request to Azure OpenAI
 * 
 * Azure requires a custom base URL (your deployment endpoint)
 * and uses api-key header instead of Bearer token.
 * 
 * @param string $prompt User's question or instruction
 * @param int $max_tokens Maximum length of response
 * @param float $temperature Creativity level
 * @return string AI response text
 */
function azure_openai_complete($prompt, $max_tokens = 150, $temperature = 0.7) {
    
    // Azure requires your custom endpoint URL
    // Format: https://{your-resource}.openai.azure.com/openai/deployments/{deployment-name}/chat/completions?api-version=2024-02-15-preview
    $base_url = getenv('AZURE_OPENAI_ENDPOINT');
    
    if (!$base_url) {
        return "[ERROR Azure: Set AZURE_OPENAI_ENDPOINT environment variable]";
    }
    
    // Payload is OpenAI-compatible
    $payload = [
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => $max_tokens,
        'temperature' => $temperature
    ];

    try {
        // Azure uses 'azure_openai' as provider to trigger special header handling
        $result = dpz_call('azure_openai', '', $payload, $base_url);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        } else {
            throw new Exception("Unexpected response format: " . json_encode($result));
        }
        
    } catch (Exception $e) {
        return "[ERROR Azure OpenAI: " . $e->getMessage() . "]";
    }
}
