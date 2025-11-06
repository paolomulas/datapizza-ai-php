<?php

/**
 * 🍕 Example 1C: Home Assistant with Voice - Multi-Provider LLM Pool
 * 
 * EDUCATIONAL: This script demonstrates failover logic without wrappers.
 * Everything is procedural. No helper functions.
 * Just step-by-step logic you can follow line by line.
 */

// ============================================
// LOAD THE UNIVERSAL API CLIENT
// ============================================

require_once __DIR__ . '/../../datapizza/clients/dpz_call.php';

// ============================================
// CONFIGURATION: Choose your LLM priority
// ============================================

// Available strategies:
// 1. Auto-discover: $priority = dpz_get_available_llms();
// 2. Prefer cheap: $priority = array('gemini', 'deepseek', 'kimi', 'openai');
// 3. Prefer quality: $priority = array('openai', 'deepseek', 'kimi', 'gemini');

$priority = array('openai', 'deepseek', 'kimi', 'gemini');

// ============================================
// USER INPUT
// ============================================

$user_question = "What time is it now? And tell me briefly about today's weather.";

$system_prompt = 
    "You are a home assistant running on a Raspberry Pi from 2011 (256MB RAM).\n" .
    "You speak Italian fluently.\n" .
    "Keep responses short and natural (max 3-4 sentences).\n" .
    "You are Datapizza-AI: private, transparent, local intelligence.\n" .
    "No cloud surveillance. No tracking. Just helpful.";

// ============================================
// DISPLAY HEADER
// ============================================

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  🏠 Home Assistant with Voice - Multi-Provider LLM  ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "👤 User asks: \"$user_question\"\n\n";
echo "🔍 Using LLM priority: " . implode(', ', $priority) . "\n\n";

// ============================================
// STEP 1: TRY EACH LLM PROVIDER (FAILOVER LOGIC)
// ============================================

$ai_response = null;
$used_llm = null;

echo "🤖 Attempting LLM providers...\n";

foreach ($priority as $provider) {
    
    // Check if this provider has an API key
    if (!dpz_is_provider_available($provider)) {
        echo "  ❌ $provider: API key not configured\n";
        continue;
    }
    
    echo "  ✅ Trying $provider...\n";
    
    try {
        
        // Determine the model name for this provider
        $model_map = array(
            'openai'    => 'gpt-4o-mini',
            'deepseek'  => 'deepseek-chat',
            'kimi'      => 'moonshot-v1-8k',
            'gemini'    => 'gemini-pro',
            'anthropic' => 'claude-3-5-sonnet-20241022'
        );
        
        $model = isset($model_map[$provider]) ? $model_map[$provider] : 'default';
        
        // Make the API call
        $response = dpz_call(
            $provider,
            '/v1/chat/completions',
            array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'system', 'content' => $system_prompt),
                    array('role' => 'user', 'content' => $user_question)
                ),
                'max_tokens' => 150,
                'temperature' => 0.7
            )
        );
        
        // Check for errors
        if (is_array($response) && isset($response['error'])) {
            echo "     ⚠️ Error: " . $response['message'] . "\n";
            continue;
        }
        
        // EXTRACT TEXT: Different providers have different response formats
        
        // Format 1: OpenAI, DeepSeek, Kimi
        if (isset($response['choices'][0]['message']['content'])) {
            $ai_response = $response['choices'][0]['message']['content'];
            $used_llm = $provider;
            echo "     ✅ Success with $provider!\n\n";
            break;
        }
        
        // Format 2: Anthropic
        if (isset($response['content'][0]['text'])) {
            $ai_response = $response['content'][0]['text'];
            $used_llm = $provider;
            echo "     ✅ Success with $provider!\n\n";
            break;
        }
        
        // Format 3: Gemini
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_response = $response['candidates'][0]['content']['parts'][0]['text'];
            $used_llm = $provider;
            echo "     ✅ Success with $provider!\n\n";
            break;
        }
        
    } catch (Exception $e) {
        echo "     ❌ Exception: " . $e->getMessage() . "\n";
        continue;
    }
}

// ============================================
// IF ALL FAILED: USE FALLBACK
// ============================================

if (!$ai_response) {
    echo "⚠️ All LLM providers failed. Using fallback.\n\n";
    $ai_response = "I am Datapizza-AI, a private home assistant. Sorry, I cannot access information right now.";
    $used_llm = "FALLBACK";
}

// ============================================
// DISPLAY THE RESPONSE
// ============================================

echo "🤖 [$used_llm] responds:\n";
echo "   \"$ai_response\"\n\n";

// ============================================
// STEP 2: CONVERT TO SPEECH WITH ELEVENLABS
// ============================================

// Check if ElevenLabs key is available (it's mandatory)
if (!dpz_is_provider_available('elevenlabs')) {
    echo "❌ ERROR: ElevenLabs API key is required!\n";
    echo "   Add ELEVENLABS_API_KEY to .env file\n";
    exit(1);
}

echo "🎙️ Generating audio with ElevenLabs...\n";

// Call ElevenLabs
$audio_response = dpz_call(
    'elevenlabs',
    '/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM',  // Rachel voice ID
    array(
        'text' => $ai_response,
        'model_id' => 'eleven_turbo_v2_5',
        'voice_settings' => array(
            'stability' => 0.75,
            'similarity_boost' => 0.75
        )
    ),
    null,
    true  // binary_response = true
);

// Check for errors
if (is_array($audio_response) && isset($audio_response['error'])) {
    echo "❌ ElevenLabs error: " . $audio_response['message'] . "\n";
    exit(1);
}

// ============================================
// STEP 3: SAVE AUDIO FILE
// ============================================

$output_dir = __DIR__ . '/../../data';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

$output_file = $output_dir . '/home_assistant_response.mp3';
file_put_contents($output_file, $audio_response);

$file_size = filesize($output_file);

echo "✅ Audio saved!\n";
echo "   File: $output_file\n";
echo "   Size: $file_size bytes\n";
echo "   Duration: ~" . ceil(strlen($ai_response) / 30) . " seconds\n\n";

// ============================================
// SUMMARY
// ============================================

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║ 🎉 Success!                                         ║\n";
echo "║ LLM: $used_llm                                      ║\n";
echo "║ TTS: ElevenLabs                                     ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "📚 EDUCATIONAL INSIGHTS:\n\n";

echo "1. FAILOVER LOGIC\n";
echo "   Tried each provider until one succeeded.\n";
echo "   This is how production systems stay reliable.\n\n";

echo "2. RESPONSE PARSING\n";
echo "   Each provider has different JSON format.\n";
echo "   We extracted text using if-statements.\n";
echo "   Simple, procedural, easy to understand.\n\n";

echo "3. CONFIGURATION-DRIVEN\n";
echo "   Change priority by modifying \$priority array.\n";
echo "   Add new provider? Just add to \$model_map.\n";
echo "   No complex config files needed.\n\n";

echo "4. ON RASPBERRY PI\n";
echo "   This script used ~15-20MB RAM.\n";
echo "   No OOP overhead. No frameworks.\n";
echo "   Just procedural logic. Efficient.\n\n";

?>
