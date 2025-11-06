<?php

/**
 * 🍕 Datapizza-AI PHP - ElevenLabs Text-to-Speech Client (Educational Edition)
 * 
 * Learn how AI text-to-speech actually works by reading this code.
 * 
 * WHY TEXT-TO-SPEECH?
 * - Chat APIs return text (boring). This one returns audio (cool!).
 * - Shows how to handle binary responses (different from JSON)
 * - TTS on Raspberry Pi = powerful - makes it talk without GPU
 * - Great for accessibility, voice assistants, art projects
 * 
 * THE KEY INSIGHT:
 * Most people think AI = neural networks = complex math.
 * But THIS part? It's just "send text → get audio bytes → save to file"
 * The complexity is on ElevenLabs' servers, not here.
 */

require_once __DIR__ . '/dpz_call.php';

/**
 * Convert text to speech
 * 
 * This is straightforward:
 * 1. Package the text + settings into a request
 * 2. Send it to ElevenLabs API
 * 3. Get back MP3 audio bytes
 * 4. Wrap it with metadata so we remember what we asked for
 * 
 * @param string $text What you want to hear
 * @param string $voice_id Which voice to use (IDs are ElevenLabs specific)
 * @param string $model_id Which TTS model - faster ones vs. better quality
 * @param array $voice_settings Optional: how emotional/varied should the voice be?
 * @return array 'audio' (binary MP3 data) and 'metadata' (what we asked for)
 */
function elevenlabs_text_to_speech($text, $voice_id = '21m00Tcm4TlvDq8ikWAM', $model_id = 'eleven_turbo_v2_5', $voice_settings = null) {
    
    $provider = "elevenlabs";
    $endpoint = "v1/text-to-speech/" . $voice_id;

    // Build the request - note how simple it is
    $payload = array(
        "text" => $text,
        "model_id" => $model_id
    );

    // Voice settings control the emotional content:
    // - stability: 0 = robotic, 1.0 = natural/consistent
    // - similarity_boost: 0 = generic, 1.0 = sounds like the actual voice
    if ($voice_settings !== null) {
        $payload["voice_settings"] = $voice_settings;
    }

    try {
        // dpz_call handles all the HTTP complexity
        // binary_response=true means "I expect audio bytes, not JSON"
        $audio_data = dpz_call($provider, $endpoint, $payload, null, true);
        
        // Wrap it: the audio bytes alone don't tell us what we asked for
        // So we attach metadata - timestamp, text, voice choice, etc.
        $result = array(
            'audio' => $audio_data,  // The actual MP3 bytes
            'metadata' => array(
                'text' => $text,
                'voice_id' => $voice_id,
                'model' => $model_id,
                'timestamp' => time(),
                'size_bytes' => strlen($audio_data)
            )
        );
        
        return $result;
        
    } catch (Exception $e) {
        throw new Exception("ElevenLabs TTS failed: " . $e->getMessage());
    }
}

/**
 * Save audio to a file
 * 
 * The audio variable holds binary MP3 data.
 * We need to write it to disk so you can play it.
 * 
 * @param array $audio_response Output from elevenlabs_text_to_speech()
 * @param string $filepath Where to save (e.g., 'output.mp3')
 * @return bool Did it work?
 */
function elevenlabs_save_audio($audio_response, $filepath) {
    
    // Defensive check: make sure we got the right data structure
    if (!isset($audio_response['audio'])) {
        throw new Exception("Expected 'audio' key in response");
    }
    
    // file_put_contents = write data to disk
    // Returns false on failure, otherwise returns bytes written
    $result = file_put_contents($filepath, $audio_response['audio']);
    
    if ($result === false) {
        throw new Exception("Failed to write audio to: $filepath");
    }
    
    return true;
}

/**
 * List all available voices
 * 
 * ElevenLabs has dozens of voices.
 * How do you know which IDs to use? Ask the API.
 * 
 * This is a GET request (not POST) - we're fetching data, not sending it.
 * 
 * @return array List of voices with names, descriptions, samples
 */
function elevenlabs_get_voices() {
    
    $provider = "elevenlabs";
    $endpoint = "v1/voices";
    
    try {
        // Get API key
        $api_key = getenv('ELEVENLABS_API_KEY');
        
        if (!$api_key) {
            throw new Exception("ELEVENLABS_API_KEY environment variable not set");
        }
        
        $url = 'https://api.elevenlabs.io/' . ltrim($endpoint, '/');
        
        // Make GET request manually (dpz_call defaults to POST)
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "xi-api-key: $api_key"
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("HTTP error $http_code");
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parse error: " . json_last_error_msg());
        }
        
        if (isset($result['voices'])) {
            return $result['voices'];
        } else {
            throw new Exception("Unexpected API response format");
        }
        
    } catch (Exception $e) {
        throw new Exception("Failed to fetch voices: " . $e->getMessage());
    }
}

/**
 * Get details about one specific voice
 * 
 * @param string $voice_id ElevenLabs voice ID
 * @return array Voice metadata: name, accent, samples, etc.
 */
function elevenlabs_get_voice($voice_id) {
    
    $api_key = getenv('ELEVENLABS_API_KEY');
    
    if (!$api_key) {
        throw new Exception("ELEVENLABS_API_KEY environment variable not set");
    }
    
    $url = 'https://api.elevenlabs.io/v1/voices/' . urlencode($voice_id);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "xi-api-key: $api_key"
    ));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception("HTTP error $http_code");
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON parse error: " . json_last_error_msg());
    }
    
    return $result;
}
