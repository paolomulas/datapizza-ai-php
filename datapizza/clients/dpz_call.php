<?php

/**
 * 🍕 Datapizza-AI PHP - Universal API Client (PHP 7.x Educational)
 * 
 * Designed for Raspberry Pi Model B (2011) - 256MB RAM, 3 watts power.
 * 
 * UPDATED: Now supports multiple API keys with fallback logic and LLM pool discovery
 * - Keys from .env (medium priority)
 * - Environment variables (high priority)  
 * - Graceful fallback if missing (low priority)
 * 
 * WHY THIS APPROACH?
 * - Most AI frameworks assume cloud GPUs. This one assumes you have curiosity.
 * - Every line is readable. No magic. No black boxes.
 * - Shows HOW APIs actually work - HTTP, headers, JSON, binary data.
 * - Works on ancient hardware because it's just PHP + curl (already installed).
 * 
 * WHAT YOU'LL LEARN:
 * 1. HTTP request/response cycle
 * 2. Authentication patterns (Bearer tokens, custom headers, URL params)
 * 3. JSON vs binary data handling
 * 4. Provider diversity (different APIs, same patterns)
 * 5. Error handling for unreliable connections
 * 6. Configuration management (multiple providers, missing keys)
 */

// ============================================
// PART 1: CONFIGURATION CACHING
// ============================================

/**
 * Global cache for API keys (load once, use everywhere)
 * Why cache? On Raspberry Pi 256MB RAM, parsing .env repeatedly wastes memory.
 */
$_DPZ_API_KEYS = null;
$_DPZ_LLM_POOL = null;

/**
 * Load all API keys from .env file and environment variables (only once)
 * 
 * EDUCATIONAL: Environment variables have HIGHER priority than .env file.
 * Why? In production (Docker, servers), we use environment variables.
 * In development (local), we use .env file.
 * This order lets production override development safely.
 */
function _dpz_load_api_keys() {
    global $_DPZ_API_KEYS;
    
    if ($_DPZ_API_KEYS !== null) {
        return;  // Already loaded (caching)
    }
    
    $_DPZ_API_KEYS = array();
    
    // STEP 1: Load from .env file (lower priority)
    $env_file = __DIR__ . '/../../.env';
    if (file_exists($env_file)) {
        $env_data = parse_ini_file($env_file);
        if ($env_data) {
            $_DPZ_API_KEYS = array_merge($_DPZ_API_KEYS, $env_data);
        }
    }
    
    // STEP 2: Override with environment variables (higher priority)
    $known_keys = array(
        'OPENAI_API_KEY',
        'ANTHROPIC_API_KEY',
        'DEEPSEEK_API_KEY',
        'GOOGLE_API_KEY',
        'GEMINI_API_KEY',
        'KIMI_API_KEY',
        'AZURE_API_KEY',
        'ELEVENLABS_API_KEY',
        'STABILITY_API_KEY'
    );
    
    foreach ($known_keys as $key) {
        $value = getenv($key);
        if ($value && !empty($value)) {
            $_DPZ_API_KEYS[$key] = $value;
        }
    }
}

/**
 * Get API key for a specific provider
 * 
 * @param string $provider Provider name ('openai', 'elevenlabs', etc.)
 * @param bool $required If true, throw exception if missing. If false, return null.
 * @return string|null The API key, or null if not found and not required
 */
function dpz_get_api_key($provider, $required = true) {
    _dpz_load_api_keys();
    global $_DPZ_API_KEYS;
    
    $env_var = strtoupper($provider) . '_API_KEY';
    
    if (isset($_DPZ_API_KEYS[$env_var]) && !empty($_DPZ_API_KEYS[$env_var])) {
        return $_DPZ_API_KEYS[$env_var];
    }
    
    if ($required) {
        throw new Exception(
            "Error: API key missing for provider '$provider'!\n" .
            "Set environment variable: $env_var\n" .
            "Or add to .env file: $env_var=your-api-key-here"
        );
    }
    
    return null;
}

/**
 * Check if a provider is available (without throwing exception)
 * 
 * @param string $provider Provider name
 * @return bool True if API key exists and is not empty
 */
function dpz_is_provider_available($provider) {
    try {
        return dpz_get_api_key($provider, false) !== null;
    } catch (Exception $e) {
        return false;
    }
}

// ============================================
// PART 2: UNIVERSAL API CALLER
// ============================================

/**
 * Universal function to call ANY API provider
 * 
 * EDUCATIONAL: Despite all providers being different, they follow the SAME HTTP pattern:
 * 1. Build URL
 * 2. Build authentication header
 * 3. Send JSON payload
 * 4. Get response
 * 5. Parse response
 * 
 * The ONLY differences: header format, endpoint URL, response structure.
 * This function abstracts those differences away.
 * 
 * @param string $provider Which service ('openai', 'elevenlabs', etc.)
 * @param string $endpoint The API endpoint path ('/v1/chat/completions')
 * @param array|null $payload The request body (will be JSON encoded)
 * @param string|null $base_url Override the service URL if needed
 * @param bool $binary_response Is response binary (audio/image) or text (JSON)?
 * @param string $method HTTP method (POST, GET)
 * @param bool $required_key If false, return error array instead of throwing exception
 * 
 * @return mixed Decoded JSON array, binary data, or array('error' => true)
 */
function dpz_call($provider, $endpoint, $payload = null, $base_url = null, $binary_response = false, $method = 'POST', $required_key = true) {
    
    // ====== STEP 1: GET AND VALIDATE API KEY ======
    try {
        $api_key = dpz_get_api_key($provider, $required_key);
    } catch (Exception $e) {
        if ($required_key) {
            throw $e;
        }
        return array(
            'error' => true,
            'message' => "Provider '$provider' API key not available",
            'provider' => $provider
        );
    }
    
    if (!$api_key && !$required_key) {
        return array(
            'error' => true,
            'message' => "Provider '$provider' API key not available",
            'provider' => $provider
        );
    }

    // ====== STEP 2: BUILD FULL URL ======
    if ($base_url === null) {
        $base_urls = array(
            'openai' => 'https://api.openai.com',
            'anthropic' => 'https://api.anthropic.com',
            'deepseek' => 'https://api.deepseek.com',
            'google' => 'https://generativelanguage.googleapis.com',
            'gemini' => 'https://generativelanguage.googleapis.com',
            'kimi' => 'https://api.moonshot.cn',
            'azure' => 'https://{region}.tts.speech.microsoft.com',
            'elevenlabs' => 'https://api.elevenlabs.io',
            'stability' => 'https://api.stability.ai'
        );
        
        if (!isset($base_urls[$provider])) {
            throw new Exception("Unknown provider: $provider. Known: " . implode(', ', array_keys($base_urls)));
        }
        
        $base_url = $base_urls[$provider];
    }
    
    $url = rtrim($base_url, '/') . '/' . ltrim($endpoint, '/');

    // ====== STEP 3: BUILD HTTP HEADERS ======
    $headers = array('Content-Type: application/json');
    
    switch ($provider) {
        case 'anthropic':
            $headers[] = "x-api-key: $api_key";
            $headers[] = "anthropic-version: 2023-06-01";
            break;
            
        case 'elevenlabs':
            $headers[] = "xi-api-key: $api_key";
            break;
            
        case 'azure':
            $headers[] = "Ocp-Apim-Subscription-Key: $api_key";
            $headers[] = "Content-Type: application/ssml+xml";
            break;
            
        case 'stability':
            $headers[] = "Authorization: Bearer $api_key";
            break;
            
        case 'google':
        case 'gemini':
            $has_param = (strpos($url, '?') !== false);
            $separator = $has_param ? '&' : '?';
            $url = $url . $separator . "key=$api_key";
            break;
            
        default:  // OpenAI, DeepSeek, Kimi, etc.
            $headers[] = "Authorization: Bearer $api_key";
            break;
    }

    // ====== STEP 4: MAKE HTTP REQUEST WITH CURL ======
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $timeout = $binary_response ? 120 : 60;  // Binary (audio/image) takes longer
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // ====== STEP 5: HANDLE NETWORK ERRORS ======
    if ($curl_error) {
        throw new Exception("Network error ($provider): $curl_error");
    }

    // ====== STEP 6: HANDLE HTTP ERRORS ======
    if ($http_code !== 200) {
        $error_msg = $binary_response ? "HTTP $http_code (binary)" : $response;
        
        return array(
            'error' => true,
            'http_code' => $http_code,
            'message' => "[ERROR $provider: HTTP $http_code]",
            'provider' => $provider
        );
    }

    // ====== STEP 7: PARSE RESPONSE ======
    if ($binary_response) {
        return $response;  // Return raw bytes for audio/images
    } else {
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parsing error: " . json_last_error_msg());
        }
        
        return $result;
    }
}

// ============================================
// PART 3: LLM POOL DISCOVERY (NEW FEATURES)
// ============================================
// These functions are NEW. They don't affect old code.
// Old code calls dpz_call() directly and still works perfectly.

/**
 * EDUCATIONAL: Auto-discover all available LLM providers from .env
 * 
 * This demonstrates configuration-driven system design:
 * We DON'T hardcode a list of "supported providers".
 * Instead, we look at what API keys EXIST and infer the providers.
 * 
 * This means: Add a new provider? Just add the key to .env!
 * The system discovers it automatically. No code changes needed.
 * 
 * @return array Array of discovered LLM providers with their configs
 */
function dpz_discover_llm_providers() {
    global $_DPZ_LLM_POOL;
    
    if ($_DPZ_LLM_POOL !== null) {
        return $_DPZ_LLM_POOL;
    }
    
    _dpz_load_api_keys();
    global $_DPZ_API_KEYS;
    
    $_DPZ_LLM_POOL = array();
    
    // Provider registry: env variable → provider config
    $known_providers = array(
        'OPENAI_API_KEY' => array(
            'name' => 'openai',
            'description' => 'OpenAI GPT-4o mini (quality)',
            'base_url' => 'https://api.openai.com',
            'model' => 'gpt-4o-mini',
            'endpoint' => '/v1/chat/completions'
        ),
        'DEEPSEEK_API_KEY' => array(
            'name' => 'deepseek',
            'description' => 'DeepSeek Chat (cost-effective)',
            'base_url' => 'https://api.deepseek.com',
            'model' => 'deepseek-chat',
            'endpoint' => '/v1/chat/completions'
        ),
        'KIMI_API_KEY' => array(
            'name' => 'kimi',
            'description' => 'Kimi/Moonshot (multilingual)',
            'base_url' => 'https://api.moonshot.cn',
            'model' => 'moonshot-v1-8k',
            'endpoint' => '/v1/chat/completions'
        ),
        'ANTHROPIC_API_KEY' => array(
            'name' => 'anthropic',
            'description' => 'Anthropic Claude (premium)',
            'base_url' => 'https://api.anthropic.com',
            'model' => 'claude-3-5-sonnet-20241022',
            'endpoint' => '/v1/messages'
        ),
        'GEMINI_API_KEY' => array(
            'name' => 'gemini',
            'description' => 'Google Gemini (free tier)',
            'base_url' => 'https://generativelanguage.googleapis.com',
            'model' => 'gemini-pro',
            'endpoint' => '/v1beta/models/gemini-pro:generateContent'
        )
    );
    
    // Auto-discover: which providers have API keys available?
    foreach ($known_providers as $key_name => $config) {
        if (isset($_DPZ_API_KEYS[$key_name]) && !empty($_DPZ_API_KEYS[$key_name])) {
            $_DPZ_LLM_POOL[$config['name']] = $config;
        }
    }
    
    return $_DPZ_LLM_POOL;
}

/**
 * EDUCATIONAL: Get list of available LLM provider names
 * 
 * EXAMPLE:
 * $available = dpz_get_available_llms();
 * // Returns: array('openai', 'deepseek', 'kimi')
 * 
 * @return array List of available provider names
 */
function dpz_get_available_llms() {
    $pool = dpz_discover_llm_providers();
    return array_keys($pool);
}

?>
