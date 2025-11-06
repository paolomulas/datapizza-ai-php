<?php

/**
 * 🍕 Datapizza-AI PHP - Universal API Client (PHP 7.x Educational)
 * 
 * Designed for Raspberry Pi Model B (2011) - 256MB RAM, 3 watts power.
 * 
 * WHY THIS APPROACH?
 * - Most AI frameworks assume cloud GPUs. This one assumes you have curiosity.
 * - Every line is readable. No magic. No black boxes.
 * - Shows HOW APIs actually work - HTTP, headers, JSON, binary data.
 * - Works on ancient hardware because it's just PHP + curl (already installed).
 * 
 * WHAT YOU'RE LEARNING:
 * 1. HTTP request/response cycle
 * 2. Authentication patterns (Bearer tokens, custom headers, URL params)
 * 3. JSON vs binary data handling
 * 4. Provider diversity (different APIs, same patterns)
 * 5. Error handling for unreliable connections
 */

/**
 * Send a request to any AI service provider
 * 
 * This is THE foundation. Everything else uses this.
 * It demonstrates:
 * - How to talk to remote APIs
 * - How providers handle authentication differently
 * - How to deal with both text (JSON) and binary (audio/images) responses
 * 
 * Think of this like learning to "curl" from PHP instead of from command line.
 * Once you understand THIS, you understand all API communication.
 * 
 * @param string $provider Which service ('openai', 'elevenlabs', etc.)
 * @param string $endpoint The specific API path ('/v1/chat/completions')
 * @param array $payload What we're sending (model, prompt, settings, etc.)
 * @param string|null $base_url Override the service URL if needed
 * @param bool $binary_response Is this audio/image (true) or text (false)?
 * @param string $method 'POST' for most operations, 'GET' for listing
 * @return mixed Either decoded JSON array or raw binary data
 */
function dpz_call($provider, $endpoint, $payload = null, $base_url = null, $binary_response = false, $method = 'POST') {
    
    // =============================================================
    // STEP 1: Get the API key - secrets stored in environment, not code
    // =============================================================
    // Why environment variables? Because hardcoding secrets = disaster.
    // On Raspberry Pi: export OPENAI_API_KEY="sk-..." before running script
    
    $env_var = strtoupper($provider) . '_API_KEY';
    $api_key = getenv($env_var);
    
    if (!$api_key) {
        throw new Exception(
            "Error: API key missing!\n" .
            "Set this environment variable: $env_var\n" .
            "Example: export $env_var='your-api-key-here'\n" .
            "Then run: php your_script.php"
        );
    }

    // =============================================================
    // STEP 2: Build the full URL
    // =============================================================
    // Each AI service has a different base URL. We normalize it.
    // Educational note: This is why API documentation always shows the base URL first.
    
    if ($base_url === null) {
        $base_urls = array(
            'openai' => 'https://api.openai.com',
            'anthropic' => 'https://api.anthropic.com',
            'deepseek' => 'https://api.deepseek.com',
            'google' => 'https://generativelanguage.googleapis.com',
            'azure' => 'https://{region}.tts.speech.microsoft.com',
            'elevenlabs' => 'https://api.elevenlabs.io',
            'stability' => 'https://api.stability.ai'
        );
        
        if (!isset($base_urls[$provider])) {
            throw new Exception("Unknown provider: $provider. Known: " . implode(', ', array_keys($base_urls)));
        }
        
        $base_url = $base_urls[$provider];
    }
    
    // Combine base URL + endpoint = complete URL
    // Example: 'https://api.openai.com' + 'v1/chat/completions' 
    //        = 'https://api.openai.com/v1/chat/completions'
    $url = rtrim($base_url, '/') . '/' . ltrim($endpoint, '/');

    // =============================================================
    // STEP 3: Build headers - this is where provider differences show!
    // =============================================================
    // Every service wants its API key in a different place/format.
    // This is the hardest part to understand - memorize these patterns.
    
    $headers = array('Content-Type: application/json');
    
    switch ($provider) {
        // Anthropic (Claude)
        case 'anthropic':
            // Note: NOT "Authorization: Bearer"
            // Anthropic uses custom "x-api-key" header (like many non-OpenAI services)
            $headers[] = "x-api-key: $api_key";
            // Anthropic also requires API version - they iterate fast
            $headers[] = "anthropic-version: 2023-06-01";
            break;
            
        // ElevenLabs (Text-to-Speech)
        case 'elevenlabs':
            // Another custom header pattern: "xi-api-key"
            // Pattern: smaller services often use custom headers
            $headers[] = "xi-api-key: $api_key";
            break;
            
        // Azure (Microsoft cloud services)
        case 'azure':
            // Azure uses "Ocp-Apim-Subscription-Key" - very Microsoft-style naming
            $headers[] = "Ocp-Apim-Subscription-Key: $api_key";
            // Azure TTS expects SSML (Speech Synthesis Markup Language), not JSON
            $headers[] = "Content-Type: application/ssml+xml";
            break;
            
        // Stability AI (Image generation)
        case 'stability':
            // Back to Bearer token format, like OpenAI
            $headers[] = "Authorization: Bearer $api_key";
            break;
            
        // Google Cloud services
        case 'google':
            // Google is unique: API key goes in the URL, not headers
            // This is less secure for some use cases, but that's their design
            $has_param = (strpos($url, '?') !== false);
            $separator = $has_param ? '&' : '?';
            $url = $url . $separator . "key=$api_key";
            break;
            
        // OpenAI, DeepSeek, and most modern services
        default:
            // The "standard" pattern: Authorization Bearer token
            // This became the de-facto standard because OpenAI did it
            $headers[] = "Authorization: Bearer $api_key";
            break;
    }

    // =============================================================
    // STEP 4: Make the HTTP request using curl
    // =============================================================
    // curl is the foundation of all PHP API work
    // It's low-level enough to understand, high-level enough to be practical
    
    $ch = curl_init($url);
    
    // Tell curl to return response as string (not print it)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // How long should we wait? Audio/image generation takes longer than chat.
    // Raspberry Pi is slow, so we need patience.
    $timeout = $binary_response ? 120 : 60;  // seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    
    // Send custom headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // For POST requests, send the JSON payload
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        // json_encode converts PHP array to JSON string that API understands
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    
    // Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // =============================================================
    // STEP 5: Handle network problems
    // =============================================================
    // Raspberry Pi on WiFi = unreliable connection possible
    // We need to catch curl-level errors separately from HTTP errors
    
    if ($curl_error) {
        // This is a network problem (DNS, connection timeout, etc.)
        throw new Exception("Network error: $curl_error");
    }

    // =============================================================
    // STEP 6: Handle HTTP errors
    // =============================================================
    // 200 = success
    // 4xx = we did something wrong (bad API key, wrong format, etc.)
    // 5xx = service is having problems
    
    if ($http_code !== 200) {
        if ($binary_response) {
            // For audio/images, response body is binary, can't show it as text
            $error_msg = "HTTP $http_code (binary response - check your API key and request format)";
        } else {
            // For JSON responses, try to show the error message
            $error_msg = $response;
        }
        throw new Exception("HTTP error $http_code: $error_msg");
    }

    // =============================================================
    // STEP 7: Decode response based on type
    // =============================================================
    // This is the KEY INSIGHT: JSON and binary need different handling
    
    if ($binary_response) {
        // Audio (MP3), images (PNG/JPEG), video - return raw bytes
        // These will be saved to files, not displayed as text
        return $response;
        
    } else {
        // Text responses: JSON that needs to become a PHP array
        // json_decode($string, true) means "return as associative array"
        $result = json_decode($response, true);
        
        // json_last_error checks if decoding worked
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parsing error: " . json_last_error_msg());
        }
        
        return $result;
    }
}

// =============================================================
// EDUCATIONAL COMMENTS:
// =============================================================
//
// What you just learned:
// 1. Environment variables = secure API key storage
// 2. Different providers = different URL structures & headers
// 3. curl = the foundation of HTTP in PHP
// 4. HTTP status codes = how servers talk back
// 5. JSON = how APIs transfer structured data
// 6. Binary = how they transfer audio, images, video
//
// This 140 lines of code (with comments) explains how ~90% of API 
// integrations work. Everything else is just variations on this theme.
//
// On a Raspberry Pi Model B (256MB RAM):
// - Each curl request uses ~2-5MB (small)
// - JSON parsing uses ~1-2MB
// - The entire script runs in <10MB
// - Perfect for embedded systems
//
// Try it: php -S localhost:8080 && curl localhost:8080/your_script.php
// The Raspberry Pi handles it fine.
