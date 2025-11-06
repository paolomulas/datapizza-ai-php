<?php

/**
 * 🍕 Example 1B: Your First Audio Call from a Raspberry Pi (2011!)
 * 
 * You just made a chat AI understand text.
 * Now let's make it SPEAK.
 * 
 * This example shows:
 * - How binary responses work (audio, not JSON text)
 * - Text-to-Speech from a provider like ElevenLabs
 * - Saving generated audio to disk
 * - Why APIs return different data types
 * 
 * Key insight: JSON responses are decoded. Binary responses are saved.
 * That's the ONLY difference from hello_pizza.php
 */

// ============================================
// 🔧 STEP 1: CHOOSE YOUR TTS CLIENT
// ============================================
// Uncomment ONE of the following:

require_once __DIR__ . '/../../datapizza/clients/elevenlabs.php';    // ✅ ElevenLabs (best quality)
// require_once __DIR__ . '/../../datapizza/clients/google_tts.php';   // Google Cloud TTS
// require_once __DIR__ . '/../../datapizza/clients/azure_tts.php';    // Azure Speech Services
// require_once __DIR__ . '/../../datapizza/clients/amazon_tts.php';   // Amazon Polly

// ============================================
// 🔑 STEP 2: LOAD API KEYS
// ============================================
// All API keys are read from .env file
// Make sure you have the correct key for your chosen provider!
//
// Required .env keys:
// - ElevenLabs: ELEVENLABS_API_KEY
// - Google TTS: GOOGLE_API_KEY
// - Azure TTS: AZURE_API_KEY
// - Amazon TTS: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY

$env = parse_ini_file(__DIR__ . '/../../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

// ============================================
// 🚀 STEP 3: GENERATE SPEECH
// ============================================
// Uncomment the ONE that matches your client above:

$client_name = "ElevenLabs";
$text = "Hello from Datapizza AI PHP! This framework runs on a Raspberry Pi with zero dependencies.";

$audio = elevenlabs_text_to_speech(
    $text,
    '21m00Tcm4TlvDq8ikWAM',  // Rachel voice
    'eleven_turbo_v2_5',      // Fast model
    array(
        'stability' => 0.75,
        'similarity_boost' => 0.75
    )
);

// $client_name = "Google Cloud TTS";
// $audio = google_tts_synthesize($text);

// $client_name = "Azure Speech Services";
// $audio = azure_tts_synthesize($text);

// $client_name = "Amazon Polly";
// $audio = amazon_tts_synthesize($text);

// ============================================
// 💾 SAVE TO FILE
// ============================================
// Binary response means we get audio bytes, not JSON
// We need to save it to a file

$output_dir = __DIR__ . '/../../data';

if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

$output_file = $output_dir . '/elevenlabs_output.mp3';

try {
    elevenlabs_save_audio($audio, $output_file);
} catch (Exception $e) {
    echo "❌ Error saving audio: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================================
// 📺 DISPLAY RESULT
// ============================================
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 Your First Audio Call from Raspberry Pi     ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "🤖 Provider: $client_name\n";
echo "🎤 Voice: Rachel\n";
echo "📝 Text: \"$text\"\n\n";

$metadata = $audio['metadata'];
echo "📊 Audio Metadata:\n";
echo "  • Size: " . $metadata['size_bytes'] . " bytes\n";
echo "  • Model: " . $metadata['model'] . "\n";
echo "  • Duration: ~3 seconds\n\n";

echo "💾 Saved to: $output_file\n";
echo "📁 Filesize: " . filesize($output_file) . " bytes\n\n";

echo "🎵 How to listen:\n";
echo "  mpg123 $output_file\n";
echo "  # or download and play on your computer\n\n";

echo "✅ Success! Audio generated and saved! 🎉\n";

/**
 * 🎓 What just happened?
 * 
 * THE FLOW:
 * ─────────
 * 1. Your PHP script sent an HTTP POST request to ElevenLabs API
 * 2. ElevenLabs server converted text to audio (neural network)
 * 3. Returned MP3 bytes (not JSON!)
 * 4. You saved those bytes to a file on disk
 * 5. Now you can play it!
 * 
 * KEY DIFFERENCE FROM hello_pizza.php:
 * ────────────────────────────────────
 * 
 * JSON responses (text APIs):
 *   $response = curl_exec($ch);
 *   $data = json_decode($response, true);  // Parse JSON
 *   echo $data['choices'][0]['message']['content'];
 * 
 * Binary responses (audio APIs):
 *   $response = curl_exec($ch);
 *   file_put_contents($filename, $response);  // Save directly
 *   // Can't decode as JSON - it's audio!
 * 
 * That's the ONLY difference.
 * 
 * PARAMETERS EXPLAINED:
 * ────────────────────
 * elevenlabs_text_to_speech(text, voice_id, model_id, voice_settings)
 * 
 * - text: What you want to hear (max ~5000 chars)
 * - voice_id: Which voice to use (ElevenLabs specific)
 * - model_id: Which TTS model:
 *   • 'eleven_turbo_v2_5' = fastest (milliseconds)
 *   • 'eleven_multilingual_v1' = best quality
 * - voice_settings: Optional tweaks:
 *   • stability (0.0-1.0): how consistent/robotic
 *   • similarity_boost (0.0-1.0): how faithful to voice
 * 
 * ALL TTS CLIENTS HAVE SIMILAR APIs:
 * ──────────────────────────────────
 * 
 * ElevenLabs:
 *   elevenlabs_text_to_speech(text, voice_id, model, settings)
 * 
 * Google Cloud TTS:
 *   google_tts_synthesize(text, language, gender)
 * 
 * Azure Speech:
 *   azure_tts_synthesize(text, voice_name, rate)
 * 
 * They're all slightly different, but the PATTERN is the same:
 * 1. Send text + settings
 * 2. Get back audio bytes
 * 3. Save to file
 * 
 * WHY THIS MATTERS:
 * ────────────────
 * Audio APIs show you how different data types work:
 * 
 * ✅ JSON responses (OpenAI, DeepSeek):
 *    - Structured text data
 *    - Need parsing
 *    - Decoded to arrays
 * 
 * ✅ Binary responses (ElevenLabs, Google TTS):
 *    - Audio files (MP3, WAV)
 *    - Need file I/O
 *    - Saved directly to disk
 * 
 * ✅ Streaming responses (newer GPTs):
 *    - Token-by-token data
 *    - Progressive output
 *    - Need event parsing
 * 
 * Understanding these patterns = understanding ALL APIs.
 * 
 * AVAILABLE TTS CLIENTS:
 * ─────────────────────
 * ✅ ElevenLabs    - Best quality, natural voices
 * ✅ Google Cloud  - Multilingual, fast
 * ✅ Azure Speech  - Enterprise support
 * ✅ Amazon Polly  - Very cheap at scale
 * 
 * Each is a single PHP file (200-300 lines).
 * Each uses curl to make HTTP requests.
 * Each handles binary responses the same way.
 * 
 * That's the beauty: Binary data doesn't have to be complicated.
 * Understanding beats horsepower. 🚀🍕
 */
?>
