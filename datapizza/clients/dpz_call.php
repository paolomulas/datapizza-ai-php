<?php

/**
 * 🍕 Datapizza-AI PHP - Universal API Client
 * 
 * This is the foundation of our API-first architecture.
 * One simple function that talks to any AI service (OpenAI, Anthropic, 
 * DeepSeek, Google, etc.) using plain PHP curl.
 * 
 * Educational concepts:
 * - HTTP API communication without dependencies
 * - Environment variable management for API keys
 * - Error handling for network requests
 * - Provider abstraction pattern
 */

/**
 * Makes HTTP POST requests to various AI service providers
 * 
 * This function handles all the HTTP complexity so that individual
 * provider clients (openai.php, anthropic.php, etc.) can focus
 * on their specific payload formats.
 * 
 * How it works:
 * 1. Reads API key from environment variables
 * 2. Builds provider-specific headers
 * 3. Makes HTTP POST with JSON payload
 * 4. Returns decoded response
 * 
 * @param string $provider Provider name ('openai', 'anthropic', 'deepseek', 'google')
 * @param string $endpoint API endpoint path (e.g., 'v1/chat/completions')
 * @param array $payload JSON payload to send (model, messages, temperature, etc.)
 * @param string|null $base_url Optional custom base URL (for Azure, self-hosted, etc.)
 * @return array Decoded JSON response from the API
 * @throws Exception If request fails or response is invalid
 */
function dpz_call($provider, $endpoint, $payload, $base_url = null) {
    
    // Step 1: Get API key from environment
    // We use environment variables to keep secrets out of code
    $env_var = strtoupper($provider) . '_API_KEY';
    $api_key = getenv($env_var);
    
    if (!$api_key) {
        throw new Exception("Error: API key not found for provider '$provider'. Set $env_var environment variable.");
    }

    // Step 2: Build the full URL
    // Each provider has a different base URL structure
    if ($base_url === null) {
        $base_urls = [
            'openai' => 'https://api.openai.com',
            'anthropic' => 'https://api.anthropic.com',
            'deepseek' => 'https://api.deepseek.com',
            'google' => 'https://generativelanguage.googleapis.com'
        ];
        $base_url = $base_urls[$provider] ?? throw new Exception("Unknown provider: $provider");
    }
    
    $url = rtrim($base_url, '/') . '/' . ltrim($endpoint, '/');

    // Step 3: Prepare headers based on provider
    // Each AI service expects authentication in a slightly different format
    $headers = ['Content-Type: application/json'];
    
    switch ($provider) {
        case 'anthropic':
            // Anthropic uses x-api-key header and requires anthropic-version
            $headers[] = "x-api-key: $api_key";
            $headers[] = "anthropic-version: 2023-06-01";
            break;
        case 'google':
            // Google uses API key as URL parameter, not in headers
            $url .= "?key=$api_key";
            break;
        default:
            // OpenAI, DeepSeek, and most others use Bearer token
            $headers[] = "Authorization: Bearer $api_key";
            break;
    }

    // Step 4: Make HTTP POST request using curl
    // curl is available in PHP by default - no dependencies needed
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Step 5: Handle errors
    if ($curl_error) {
        throw new Exception("Curl error: $curl_error");
    }

    if ($http_code !== 200) {
        throw new Exception("HTTP error $http_code: $response");
    }

    // Step 6: Decode and return JSON response
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg());
    }

    return $result;
}
