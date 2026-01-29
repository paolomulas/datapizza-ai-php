<?php
/**
 * Datapizza Voice Assistant - Wake Word Monitor
 * Version 4.0 - Emotional Voice Edition 🎭
 * 
 * NEW: Empathetic voice responses with emotional intelligence
 * 
 * @author Paolo Mulas
 */

// Load .env
$env_file = __DIR__ . '/../../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// LOAD: Conversation Memory Module
require_once __DIR__ . '/../../datapizza/memory/conversation_memory.php';

// Configuration
$CONFIG = array(
    'wake_word' => 'hey datapizza',
    'trigger_file' => '/tmp/datapizza_voice.txt',
    'poll_interval' => 300000,
    'cooldown_time' => 2,
    'log_file' => __DIR__ . '/../../data/logs/voice.log',
    'audio_file' => __DIR__ . '/../../data/voice/response.wav',
    'session_id' => 'voice_default',
    'max_messages' => 20,
    'player_cmd' => 'paplay',
    'mistral_key' => getenv('MISTRAL_API_KEY'),
    'elevenlabs_key' => getenv('ELEVENLABS_API_KEY'),
    'system_prompt' => 
        'Sei Datapizza, assistente vocale italiano su Raspberry Pi. ' .
        'Rispondi con MAX 1-2 FRASI BREVI (10-20 parole). ' .
        'Usa il contesto della conversazione per follow-up intelligenti.',
    'voice_id' => '21m00Tcm4TlvDq8ikWAM'
);

// ============================================================================
// EMOTIONAL INTELLIGENCE - NEW! 🎭
// ============================================================================

/**
 * Rileva emozione appropriata dalla domanda e risposta
 * Educational: Analisi keyword + sentiment detection
 */
function detect_emotion($question, $ai_response) {
    $q_lower = strtolower($question);
    $r_lower = strtolower($ai_response);
    
    // 1. Gratitudine → cheerful
    if (preg_match('/grazie|perfetto|ottimo|fantastico|bene|eccellente/i', $q_lower)) {
        return 'cheerful';
    }
    
    // 2. Saluti → friendly
    if (preg_match('/^(ciao|buongiorno|buonasera|hey|salve)/i', $q_lower)) {
        return 'friendly';
    }
    
    // 3. Domande pensose → thoughtful
    if (preg_match('/perch[eé]|come mai|spiegami|come funziona|cosa significa/i', $q_lower)) {
        return 'thoughtful';
    }
    
    // 4. Urgenza/problemi → concerned
    if (preg_match('/aiuto|problema|errore|urgente|non funziona|rotto/i', $q_lower)) {
        return 'concerned';
    }
    
    // 5. Calcoli/numeri → confident
    if (preg_match('/\d+.*[x\*÷\/\+\-].*\d+|quanto fa|calcola/i', $q_lower)) {
        return 'confident';
    }
    
    // 6. Scherzi/battute → playful
    if (preg_match('/barzelletta|scherzo|divertente|ridere/i', $q_lower)) {
        return 'playful';
    }
    
    // 7. Default: natural
    return 'natural';
}

// ============================================================================
// API FUNCTIONS
// ============================================================================

function api_json($url, $key, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code === 200) ? json_decode($response, true) : null;
}

function api_binary($url, $key, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'xi-api-key: ' . $key,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code === 200) ? $response : null;
}

/**
 * ElevenLabs TTS con Emotional Intelligence
 * NEW: Audio tags per voce empatica
 */
/**
 * ElevenLabs TTS con Emotional Intelligence - SINTASSI CORRETTA
 * Audio tags: [brackets] non *asterischi*
 */
function call_elevenlabs($text, $config, $emotion = 'natural') {
    // Mappa emozioni → audio tags ElevenLabs (SINTASSI CORRETTA)
    $emotion_tags = array(
        'natural' => '',
        'cheerful' => '[excited]',           // ✅ Brackets!
        'friendly' => '[cheerful]',
        'thoughtful' => '[thoughtful]',
        'concerned' => '[concerned]',
        'confident' => '[confidently]',
        'playful' => '[laughs]',             // ✅ Nuovo tag
        'calm' => '[calmly]',
        'whisper' => '[whispers]'
    );
    
    // Aggiungi tag all'inizio del testo
    $tag = $emotion_tags[$emotion] ?? '';
    $enhanced_text = $tag ? "$tag $text" : $text;
    
    // DEBUG: Mostra testo con tag
    if ($tag) {
        echo "      \033[0;90m   Text with tag: \"$enhanced_text\"\033[0m\n";
    }
    
    $data = array(
        'text' => $enhanced_text,
        
        // MODELLO: Usa Turbo v2.5 (supporta audio tags base)
        // Per emozioni MOLTO evidenti, usare: 'eleven_multilingual_v2'
        'model_id' => 'eleven_turbo_v2_5',
        
        'output_format' => 'pcm_16000',
        'voice_settings' => array(
            'stability' => 0.50,             // ← RIDOTTO per più variazione
            'similarity_boost' => 0.75,
            'style' => 1.0,                  // ← MASSIMO per espressività
            'use_speaker_boost' => true      // ← NUOVO: enfatizza emozioni
        )
    );
    
    return api_binary(
        'https://api.elevenlabs.io/v1/text-to-speech/' . $config['voice_id'],
        $config['elevenlabs_key'],
        $data
    );
}


function write_log($config, $msg) {
    $dir = dirname($config['log_file']);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($config['log_file'], '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

// ============================================================================
// PROCESS COMMAND - CON EMOTIONAL INTELLIGENCE
// ============================================================================

function process_command($question, $config) {
    $start = microtime(true);
    
    echo "\n   📝 Domanda: \"$question\"\n";
    
    // DEBUG STEP 1: Carica memoria
    echo "   \033[1;35m[DEBUG 1/5] Caricamento memoria...\033[0m\n";
    
    $messages = memory_get_context($config['session_id'], $config['system_prompt']);
    $context_size = count($messages) - 1;
    
    echo "      \033[0;36m→ Messaggi totali: " . count($messages) . " (system + $context_size conversazione)\033[0m\n";
    
    if ($context_size > 0) {
        echo "      \033[1;32m🧠 Memoria ATTIVA: $context_size messaggi\033[0m\n";
        
        $recent = array_slice($messages, -4);
        foreach ($recent as $msg) {
            $role_icon = $msg['role'] === 'user' ? '👤' : '🤖';
            $content_preview = substr($msg['content'], 0, 40) . '...';
            echo "      \033[0;90m  $role_icon [{$msg['role']}]: $content_preview\033[0m\n";
        }
    } else {
        echo "      \033[0;33m⚠️  Memoria VUOTA - prima conversazione\033[0m\n";
    }
    
    $memory_file = __DIR__ . '/../../data/conversations/' . $config['session_id'] . '.json';
    echo "      \033[0;90m  File: $memory_file\033[0m\n";
    
    if (file_exists($memory_file)) {
        $size = filesize($memory_file);
        echo "      \033[0;90m  Size: " . round($size / 1024, 2) . " KB\033[0m\n";
    }
    
    echo "\n";
    
    // DEBUG STEP 2: Messages LLM
    $messages[] = array(
        'role' => 'user',
        'content' => $question
    );
    
    echo "   \033[1;35m[DEBUG 2/5] Messages inviati a LLM:\033[0m\n";
    echo "      \033[0;90m→ Totale messaggi: " . count($messages) . "\033[0m\n";
    
    // Step 1: LLM
    echo "\n   [1/3] 🤖 Mistral AI (con memoria)...\n";
    $llm_start = microtime(true);
    
    $data = array(
        'model' => 'mistral-tiny',
        'messages' => $messages,
        'max_tokens' => 35,
        'temperature' => 0.5
    );
    
    $response = api_json(
        'https://api.mistral.ai/v1/chat/completions',
        $config['mistral_key'],
        $data
    );
    
    if (!$response || !isset($response['choices'][0]['message']['content'])) {
        echo "      ❌ Errore LLM\n";
        return false;
    }
    
    $ai_response = trim($response['choices'][0]['message']['content']);
    $llm_time = round((microtime(true) - $llm_start) * 1000);
    
    $preview = strlen($ai_response) > 50 ? substr($ai_response, 0, 47) . '...' : $ai_response;
    echo "      ✅ Risposta ({$llm_time}ms): \"$preview\"\n";
    echo "      \033[0;90m   Full: \"$ai_response\"\033[0m\n\n";
    
    // DEBUG STEP 3: Salvataggio memoria
    echo "   \033[1;35m[DEBUG 3/5] Salvataggio memoria...\033[0m\n";
    
    memory_add($config['session_id'], 'user', $question, $config['max_messages']);
    echo "      ✅ User message salvato\n";
    
    memory_add($config['session_id'], 'assistant', $ai_response, $config['max_messages']);
    echo "      ✅ Assistant message salvato\n";
    
    if (file_exists($memory_file)) {
        $new_size = filesize($memory_file);
        echo "      \033[0;32m✅ File aggiornato: " . round($new_size / 1024, 2) . " KB\033[0m\n";
    } else {
        echo "      \033[1;31m❌ ERRORE: File non creato!\033[0m\n";
    }
    
    echo "\n";
    
    write_log($config, "LLM (context:$context_size, {$llm_time}ms): $ai_response");
    
    // DEBUG STEP 4: Verifica post-save
    echo "   \033[1;35m[DEBUG 4/5] Verifica post-salvataggio...\033[0m\n";
    
    $verify_messages = memory_init($config['session_id']);
    echo "      \033[0;36m→ Messaggi ora in memoria: " . count($verify_messages) . "\033[0m\n";
    
    if (count($verify_messages) > 0) {
        $last_msg = end($verify_messages);
        echo "      \033[0;90m  Ultimo: [{$last_msg['role']}] " . substr($last_msg['content'], 0, 40) . "...\033[0m\n";
    }
    
    echo "\n";
    
    // ========================================================================
    // EMOTIONAL INTELLIGENCE - Rileva emozione appropriata
    // ========================================================================
    
    $emotion = detect_emotion($question, $ai_response);
    $emotion_emoji = array(
        'natural' => '😐',
        'cheerful' => '😊',
        'friendly' => '👋',
        'thoughtful' => '🤔',
        'concerned' => '😟',
        'confident' => '💪',
        'playful' => '😄',
        'calm' => '😌'
    );
    
    $emoji = $emotion_emoji[$emotion] ?? '🎭';
    echo "   \033[1;35m🎭 Emozione rilevata:\033[0m \033[1;36m$emotion $emoji\033[0m\n\n";
    
    // Step 2: TTS con Emozione
    echo "   [2/3] 🎵 ElevenLabs TTS (emotional)...\n";
    $tts_start = microtime(true);
    
    $audio_data = call_elevenlabs($ai_response, $config, $emotion);
    
    if (!$audio_data) {
        echo "      ❌ Errore TTS\n";
        return false;
    }
    
    $bytes = file_put_contents($config['audio_file'], $audio_data);
    $tts_time = round((microtime(true) - $tts_start) * 1000);
    
    echo "      ✅ Audio ({$tts_time}ms): " . round($bytes / 1024, 1) . " KB\n\n";
    
    // Step 3: Play
    echo "   [3/3] 🔊 Riproduzione...\n";
    $play_start = microtime(true);
    
    shell_exec($config['player_cmd'] . ' ' . escapeshellarg($config['audio_file']) . ' 2>&1');
    
    $play_time = round((microtime(true) - $play_start) * 1000);
    echo "      ✅ Completato ({$play_time}ms)\n";
    
    // DEBUG STEP 5: Performance
    $total = round((microtime(true) - $start) * 1000);
    
    echo "\n   \033[1;35m[DEBUG 5/5] Performance:\033[0m\n";
    echo "      LLM: {$llm_time}ms (" . round(($llm_time / $total) * 100) . "%)\n";
    echo "      TTS: {$tts_time}ms (" . round(($tts_time / $total) * 100) . "%)\n";
    echo "      Audio: {$play_time}ms (" . round(($play_time / $total) * 100) . "%)\n";
    echo "      TOTALE: {$total}ms\n";
    
    return true;
}

// ============================================================================
// BANNER
// ============================================================================

system('clear');

echo "\033[1;36m";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🍕 DATAPIZZA VOICE ASSISTANT - EMOTIONAL EDITION 🎭 🧠       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\033[0m\n";

echo "\033[1;33m📊 Sistema:\033[0m PHP " . PHP_VERSION . " | " . php_uname('m') . "\n";
echo "\033[1;32m🎤 Wake word:\033[0m '\033[1;36m{$CONFIG['wake_word']}\033[0m'\n";
echo "\033[1;35m🧠 Memoria:\033[0m Session '{$CONFIG['session_id']}' (max {$CONFIG['max_messages']} msg)\n";
echo "\033[1;32m📁 Input:\033[0m {$CONFIG['trigger_file']}\n";
echo "\033[1;32m⚡ LLM:\033[0m mistral-tiny (optimized)\n";
echo "\033[1;35m🎭 TTS:\033[0m ElevenLabs Turbo v2.5 (emotional)\n\n";

$stats = memory_get_stats($CONFIG['session_id']);
if ($stats['exists']) {
    echo "\033[1;35m📝 Sessione esistente:\033[0m {$stats['message_count']} msg • {$stats['file_size_kb']} KB\n\n";
}

echo "\033[1;35m🎭 EMOTIONAL INTELLIGENCE ATTIVA:\033[0m\n";
echo "   ✓ Rilevamento automatico emozioni\n";
echo "   ✓ Voce adattiva al contesto\n";
echo "   ✓ Risposte empatiche naturali\n\n";

echo "\033[1;35m🧪 DEBUG MODE:\033[0m\n";
echo "   ✓ Memoria conversazionale\n";
echo "   ✓ Performance tracking\n";
echo "   ✓ Emotion detection\n\n";

echo str_repeat("─", 64) . "\n";
echo "\033[1;32m🔊 In ascolto... (Ctrl+C per fermare)\033[0m\n";
echo str_repeat("─", 64) . "\n\n";

// ============================================================================
// MAIN LOOP
// ============================================================================

$last_mtime = 0;
$last_cmd_time = 0;
$cmd_count = 0;

if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() use ($CONFIG, $cmd_count) {
        $stats = memory_get_stats($CONFIG['session_id']);
        echo "\n\n\033[1;31m🛑 Shutdown\033[0m\n";
        echo "   Comandi: $cmd_count\n";
        echo "   Memoria: {$stats['message_count']} msg ({$stats['file_size_kb']} KB)\n";
        exit(0);
    });
}

while (true) {
    if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
    }
    
    clearstatcache();
    
    if (file_exists($CONFIG['trigger_file'])) {
        $mtime = filemtime($CONFIG['trigger_file']);
        
        if ($mtime > $last_mtime) {
            $input = trim(file_get_contents($CONFIG['trigger_file']));
            
            if (!empty($input)) {
                $now = time();
                
                if (($now - $last_cmd_time) < $CONFIG['cooldown_time']) {
                    $last_mtime = $mtime;
                    continue;
                }
                
                if (stripos($input, $CONFIG['wake_word']) === 0) {
                    $command = trim(substr($input, strlen($CONFIG['wake_word'])));
                    
                    if (!empty($command)) {
                        $cmd_count++;
                        
                        echo "\n\033[1;36m" . str_repeat("═", 64) . "\033[0m\n";
                        echo "\033[1;32m🔊 WAKE WORD! Comando #$cmd_count\033[0m\n";
                        echo "\033[1;36m" . str_repeat("─", 64) . "\033[0m\n";
                        
                        if (process_command($command, $CONFIG)) {
                            echo "\n\033[1;32m✅ Completato!\033[0m\n";
                            echo "\033[1;36m" . str_repeat("═", 64) . "\033[0m\n\n";
                            $last_cmd_time = $now;
                        } else {
                            echo "\n\033[1;31m❌ Fallito\033[0m\n\n";
                        }
                    }
                }
            }
            
            $last_mtime = $mtime;
        }
    }
    
    usleep($CONFIG['poll_interval']);
}
?>
