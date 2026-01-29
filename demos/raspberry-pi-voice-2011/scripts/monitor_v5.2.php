<?php
/**
 * DataPizza Voice Assistant v5.3 FAST RADIO - EUROPEAN AGENTS EDITION 🇪🇺🛠️
 * 
 * Differenze chiave vs 5.2.1:
 * - RADIO: fast-path → nessuna chiamata ElevenLabs, parte subito lo stream
 * - Nessun cambiamento strutturale su news/meteo/agent
 * 
 * Stack:
 * 🇬🇧 Raspberry Pi Model B 2011 (Cambridge, UK)
 * 🇫🇷 Mistral AI Agents API (Paris, France)
 * 🇬🇧 ElevenLabs Turbo v2.5 (London, UK)
 * 🇮🇹 DataPizza Framework (Cagliari, Italy)
 * 
 * @version 5.3.0-FAST-RADIO
 * @date 2025-11-14
 */

// Test questions with variety
$test_questions = [
    "Che tempo fa a Cagliari",
    "Cosa mi sai dire della spiaggia del Poetto?",
    "Dimmi le notizie del giorno",
    "Chi è Luciano De Crescenzo?"
];

function write_random_question_to_file($filename, $questions) {
    $random_question = $questions[array_rand($questions)];
    file_put_contents($filename, "hey datapizza " . $random_question);
}

// Esempi di test (commentati di default)
// write_random_question_to_file('/tmp/datapizza_voice.txt', $test_questions);
 file_put_contents('/tmp/datapizza_voice.txt', "hey datapizza Dimmi le notizie del giorno");
// file_put_contents('/tmp/datapizza_voice.txt', "hey datapizza Che tempo fa a Cagliari");
// file_put_contents('/tmp/datapizza_voice.txt', "hey datapizza metti indie");

$env_file = __DIR__ . '/../../../.env';
define('DPZ_BASE', realpath(__DIR__ . '/../../../'));

// Load .env
$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    putenv(trim($key) . '=' . trim($value));
}

$CONFIG = array(
    'wake_word' => 'hey datapizza',
    'trigger_file' => '/tmp/datapizza_voice.txt',
    'poll_interval' => 300000,
    'cooldown_time' => 2,
    'log_file' => DPZ_BASE . '/data/logs/voice_v5.2.log',
    'cache_file' => DPZ_BASE . '/data/cache/responses.json',
	  'last_news_file' => DPZ_BASE . '/data/cache/last_news.json',
    'daily_limit_file' => DPZ_BASE . '/data/mistral_daily_count.json',
    'session_id' => 'voice_agents_v5.2',
    'player_cmd' => 'mpg123',
    'news_feed_url' => 'https://www.bet-studio.com/api/lab/italian_news_feed.json',
    'mistral_key' => getenv('MISTRAL_API_KEY'),
    'elevenlabs_key' => getenv('ELEVENLABS_API_KEY'),
    'voice_id' => '21m00Tcm4TlvDq8ikWAM',
	// 👇 NUOVO: file sound per la radio
    'radio_intro_file' => DPZ_BASE . '/data/voice/radio_intro_static.mp3',
	'news_intro_file'  => DPZ_BASE . '/data/voice/news_intro_static.mp3', 

    // Agents API config
    'agent_id_file' => DPZ_BASE . '/data/agent_id.txt',
    'mistral_model' => 'mistral-small-latest',

    // Credit saving
    'use_cache' => true,
    'daily_mistral_limit' => 100,

    // Mistral Agents Completion params
    'max_tokens' => 40,
    'temperature' => 0.3,
    'presence_penalty' => 0.2,
    'frequency_penalty' => 0.3,
    'prompt_mode' => 'reasoning',
    'parallel_tool_calls' => true,

    // System prompt for agent
    'system_prompt' =>
        'Sei DataPizza, assistente vocale italiano su Raspberry Pi. ' .
        'REGOLE FERREE:\n' .
        '- Rispondi SEMPRE in italiano\n' .
        '- Massimo 6-8 parole per risposta\n' .
        '- Tono amichevole e diretto\n' .
        '- Mai frasi lunghe o complesse',
);

// ============================================================================
// UTILITY: Check Response Types
// ============================================================================

function is_radio_stream_tool_response($response) {
    return is_array($response) && isset($response['type']) && $response['type'] === 'RADIO_STREAM';
}

function is_news_tool_response($response) {
    return is_array($response) && isset($response['type']) && $response['type'] === 'NEWS_AUDIO';
}

// ============================================================================
// MISTRAL AGENTS API
// ============================================================================

function mistral_api_call($method, $endpoint, $data, $config) {
    $url = 'https://api.mistral.ai' . $endpoint;

    $ch = curl_init($url);
    $headers = array(
        'Authorization: Bearer ' . $config['mistral_key'],
        'Content-Type: application/json'
    );

    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15
    ));

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log("[Mistral API] CURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    }

    error_log("[Mistral API] HTTP $http_code: $response");
    return null;
}

function create_agent($config) {
    $agent_config = array(
        'model' => $config['mistral_model'],
        'name' => 'DataPizza Voice Assistant ULTRA',
        'description' => '100% European voice AI on Raspberry Pi - Italian language with 20+ local tools',
        'instructions' => $config['system_prompt'],
        'tools' => array(
            array('type' => 'web_search'),
        ),
        'completion_args' => array(
            'temperature' => 0.3,
            'max_tokens' => $config['max_tokens'],
            'presence_penalty' => 0.2,
            'frequency_penalty' => 0.3
        )
    );

    $response = mistral_api_call('POST', '/v1/agents', $agent_config, $config);

    if ($response && isset($response['id'])) {
        $dir = dirname($config['agent_id_file']);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($config['agent_id_file'], $response['id']);
        return $response['id'];
    }

    return null;
}

function get_agent_id($config) {
    if (file_exists($config['agent_id_file'])) {
        return trim(file_get_contents($config['agent_id_file']));
    }

    return create_agent($config);
}

function send_to_agent($agent_id, $question, $config) {
    $data = array(
        'agent_id' => $agent_id,
        'messages' => array(
            array(
                'role' => 'user',
                'content' => $question
            )
        )
    );

    $response = mistral_api_call('POST', '/v1/agents/completions', $data, $config);

    if ($response && isset($response['choices'][0]['message']['content'])) {
        return $response['choices'][0]['message']['content'];
    }

    return null;
}

// ============================================================================
// CACHE SYSTEM
// ============================================================================

function get_cached_response($question, $config) {
    if (!$config['use_cache']) return null;
    if (!file_exists($config['cache_file'])) return null;

    $cache = json_decode(file_get_contents($config['cache_file']), true);
    $hash = md5(strtolower(trim($question)));

    if (isset($cache[$hash])) {
        return $cache[$hash];
    }

    return null;
}

function cache_response($question, $response, $config) {
    if (!$config['use_cache']) return;

    $dir = dirname($config['cache_file']);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $cache = file_exists($config['cache_file'])
        ? json_decode(file_get_contents($config['cache_file']), true)
        : array();

    $hash = md5(strtolower(trim($question)));
    $cache[$hash] = $response;

    if (count($cache) > 100) {
        $cache = array_slice($cache, -100, 100, true);
    }

    file_put_contents($config['cache_file'], json_encode($cache));
}

// ============================================================================
// DAILY LIMIT
// ============================================================================

function check_daily_limit($config) {
    $dir = dirname($config['daily_limit_file']);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $today = date('Y-m-d');
    $data = file_exists($config['daily_limit_file'])
        ? json_decode(file_get_contents($config['daily_limit_file']), true)
        : array();

    if (!isset($data[$today])) {
        $data = array($today => 0);
    }

    if ($data[$today] >= $config['daily_mistral_limit']) {
        return false;
    }

    return true;
}

function increment_daily_limit($config) {
    $dir = dirname($config['daily_limit_file']);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $today = date('Y-m-d');
    $data = file_exists($config['daily_limit_file'])
        ? json_decode(file_get_contents($config['daily_limit_file']), true)
        : array();

    if (!isset($data[$today])) {
        $data[$today] = 0;
    }

    $data[$today]++;
    file_put_contents($config['daily_limit_file'], json_encode($data));
}

// ============================================================================
// ULTRA LOCAL TOOLS (20+ Tools, 0 Credits!)
// ============================================================================

function handle_local_tool($question) {
    $config = $GLOBALS['CONFIG'];
    $q = strtolower(trim($question));

    // === NEWS AUDIO ===
 if (preg_match('/(?:dimmi|leggi|ultime|notizie|news)(?:\s+di\s+([a-zàèéìòù\s]+))?(?:\s+del\s+giorno)?/i', $q, $m)) {
    $requested_category = null;

    if (!empty($m[1])) {
        $requested_category = normalize_news_category($m[1]);
    }

    $news_data = fetch_news_feed($config, $requested_category);

    if ($news_data) {
        return [
            'type'          => 'NEWS_AUDIO',
            'text_response' => "Perfetto. Ti leggo le ultime notizie.",
            'audio_url'     => $news_data['audio_url']
        ];
    } else {
        if ($requested_category) {
            return "Non trovo notizie per quella categoria.";
        }
        return "Non riesco a caricare le notizie.";
    }
}

    // === METEO ☁️ ===
    if (preg_match('/(?:che\s+tempo\s+fa|meteo|previsioni)\s+(?:a|su)?\s*([^?.\s]+)/i', $q, $m)) {
        $meteo_data = fetch_meteo_data($config);

        if ($meteo_data) {
            $city = $meteo_data['city'] ?? 'località sconosciuta';
            $temp = $meteo_data['temperature'] ?? 'n.d.';
            $condition = strtolower($meteo_data['condition'] ?? 'condizioni sconosciute');

            return "A $city ci sono $temp gradi, con $condition.";
        } else {
            return "Non riesco a leggere i dati meteo.";
        }
    }

    // === RADIO STREAMING 🎵 ===

    // STOP RADIO
    if (preg_match('/(?:ferma|stop|basta|spegni)\s+(?:la\s+)?(?:musica|radio)/i', $q)) {
        if (stop_radio()) {
            return "Radio fermata.";
        } else {
            return "Nessuna radio in riproduzione.";
        }
    }

    // PLAY RADIO
    if (preg_match('/(?:metti|riproduci|fammi\s+sentire|ascoltare|suona)\s+(?:la\s+)?(?:musica\s+)?(?:radio\s+)?(.+?)(?:\s+in\s+radio)?$/i', $q, $m)) {
        $genre = trim($m[1]);

        $excluded = array('notizie', 'news', 'meteo', 'tempo');
        foreach ($excluded as $word) {
            if (stripos($genre, $word) !== false) {
                return null;
            }
        }

        echo "  [Radio Tool] Cercando '$genre' su Radio Browser...\n";
        $station = search_radio_browser($genre, $config);

        if ($station) {
            return [
                'type'    => 'RADIO_STREAM',
                'station' => $station
                // niente text_response: la voce non è critica qui
            ];
        } else {
            return "Non ho trovato radio per $genre.";
        }
    }

    // === SALUTI CONTESTUALI ===
    if (preg_match('/^(ciao|hey|salve|buongiorno|buonasera)$/i', $q)) {
        $hour = (int)date('H');
        if ($hour < 12) return "Buongiorno, come posso aiutarti?";
        if ($hour < 18) return "Buon pomeriggio, dimmi pure.";
        return "Buonasera, tutto bene?";
    }

    if (preg_match('/come\s+stai/i', $q)) return "Sto bene, grazie.";
    if (preg_match('/grazie/i', $q)) return "Prego, è un piacere.";
    if (preg_match('/perfetto|ottimo|fantastico|bravo/i', $q)) return "Contento di esserti utile.";
    if (preg_match('/scusa|scusami/i', $q)) return "Nessun problema, tranquillo.";
    if (preg_match('/arrivederci|addio|ciao\s+ciao/i', $q)) return "Ciao, a presto.";

    // === ORA E DATA ===
    if (preg_match('/che\s+(ora|ore)\s+(è|sono)/i', $q)) {
        return "Sono le " . date('H:i') . ".";
    }

    if (preg_match('/che\s+giorno/i', $q)) {
        $days = ['domenica', 'lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato'];
        $months = ['gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno',
                   'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
        return "Oggi è " . $days[date('w')] . " " . date('j') . " " . $months[date('n')-1] . ".";
    }

    // === CALCOLI MATEMATICI ===
    if (preg_match('/(\d+)\s*\+\s*(\d+)/i', $q, $m)) {
        return "Risultato: " . ($m[1] + $m[2]) . ".";
    }

    if (preg_match('/(\d+)\s*\-\s*(\d+)/i', $q, $m)) {
        return "Risultato: " . ($m[1] - $m[2]) . ".";
    }

    if (preg_match('/(\d+)\s*(?:\*|per|x)\s*(\d+)/i', $q, $m)) {
        return "Risultato: " . ($m[1] * $m[2]) . ".";
    }

    if (preg_match('/(\d+)\s*(?:\/|diviso)\s*(\d+)/i', $q, $m)) {
        if ($m[2] == 0) return "Non posso dividere per zero.";
        return "Risultato: " . round($m[1] / $m[2], 2) . ".";
    }

    if (preg_match('/(\d+)\s*%\s*di\s*(\d+)/i', $q, $m)) {
        return "Risultato: " . round(($m[1] / 100) * $m[2], 2) . ".";
    }

    if (preg_match('/radice\s+(?:quadrata\s+)?(?:di\s+)?(\d+)/i', $q, $m)) {
        return "Radice quadrata: " . round(sqrt($m[1]), 2) . ".";
    }

    if (preg_match('/(\d+)\s+(?:alla|elevato)\s+(\d+)/i', $q, $m)) {
        return "Risultato: " . pow($m[1], $m[2]) . ".";
    }

    // === CONVERSIONI ===
    if (preg_match('/(\d+)\s*(?:gradi\s+)?celsius\s+in\s+fahrenheit/i', $q, $m)) {
        $c = $m[1];
        $f = ($c * 9/5) + 32;
        return "$c gradi Celsius sono " . round($f, 1) . " Fahrenheit.";
    }

    if (preg_match('/(\d+)\s*(?:gradi\s+)?fahrenheit\s+in\s+celsius/i', $q, $m)) {
        $f = $m[1];
        $c = ($f - 32) * 5/9;
        return "$f gradi Fahrenheit sono " . round($c, 1) . " Celsius.";
    }

    if (preg_match('/(\d+)\s*(?:km|chilometri)\s+in\s+(?:miglia|miles)/i', $q, $m)) {
        $miles = $m[1] * 0.621371;
        return "{$m[1]} km sono " . round($miles, 2) . " miglia.";
    }

    if (preg_match('/(\d+)\s*(?:miglia|miles)\s+in\s+(?:km|chilometri)/i', $q, $m)) {
        $km = $m[1] * 1.60934;
        return "{$m[1]} miglia sono " . round($km, 2) . " km.";
    }

    if (preg_match('/(\d+)\s*(?:kg|chili)\s+in\s+(?:libbre|lb|pounds)/i', $q, $m)) {
        $lb = $m[1] * 2.20462;
        return "{$m[1]} kg sono " . round($lb, 2) . " libbre.";
    }

    if (preg_match('/(\d+)\s*(?:libbre|lb|pounds)\s+in\s+(?:kg|chili)/i', $q, $m)) {
        $kg = $m[1] / 2.20462;
        return "{$m[1]} lb sono " . round($kg, 2) . " kg.";
    }

    // === RASPBERRY PI STATUS ===
    if (preg_match('/temperatura\s+(?:cpu|pi|raspberry)/i', $q)) {
        $temp = trim(shell_exec('vcgencmd measure_temp | cut -d= -f2'));
        return "Il Raspberry è a $temp.";
    }

    if (preg_match('/(?:carico|uso)\s+cpu/i', $q)) {
        $load = sys_getloadavg();
        return "Carico CPU: " . round($load[0], 2) . ".";
    }

    if (preg_match('/(?:uso\s+)?(?:ram|memoria)/i', $q)) {
        $free = trim(shell_exec('free -m | grep Mem | awk \'{print $3"/"$2" MB"}\''));
        return "RAM: $free usati.";
    }

    if (preg_match('/(?:spazio\s+)?disco/i', $q)) {
        $used = trim(shell_exec('df -h / | tail -1 | awk \'{print $3}\''));
        $free = trim(shell_exec('df -h / | tail -1 | awk \'{print $4}\''));
        return "Disco: $used usati, $free liberi.";
    }

    if (preg_match('/uptime|da\s+quanto|acceso/i', $q)) {
        $uptime = trim(shell_exec('uptime -p | sed "s/up/acceso da/"'));
        return "Sono $uptime.";
    }

    // === NETWORK INFO ===
    if (preg_match('/(?:mio\s+)?ip|indirizzo/i', $q)) {
        $ip = trim(shell_exec('hostname -I | awk \'{print $1}\''));
        return "IP locale: $ip.";
    }

    if (preg_match('/(?:test\s+)?(?:ping|connessione|internet)/i', $q)) {
        exec('ping -c 1 -W 2 8.8.8.8', $output, $ret);
        return ($ret === 0) ? "Connessione ok." : "Nessuna connessione.";
    }

    // === TRADUZIONI SEMPLICI IT/EN ===
    $translations = array(
        'ciao' => 'hello',
        'grazie' => 'thank you',
        'prego' => 'you\'re welcome',
        'buongiorno' => 'good morning',
        'buonasera' => 'good evening',
        'buonanotte' => 'good night',
        'sì' => 'yes',
        'no' => 'no',
        'casa' => 'home',
        'acqua' => 'water',
        'hello' => 'ciao',
        'thank you' => 'grazie',
        'good morning' => 'buongiorno',
        'yes' => 'sì',
        'home' => 'casa',
        'water' => 'acqua'
    );

    if (preg_match('/traduci\s+(?:in\s+(?:inglese|english)\s+)?[\'"]?([a-zàèéìòùç\s]+)[\'"]?/i', $q, $m)) {
        $word = strtolower(trim($m[1]));
        if (isset($translations[$word])) {
            return "In inglese: " . $translations[$word] . ".";
        }
    }

    if (preg_match('/traduci\s+(?:in\s+italiano\s+)?[\'"]?([a-z\s]+)[\'"]?/i', $q, $m)) {
        $word = strtolower(trim($m[1]));
        if (isset($translations[$word])) {
            return "In italiano: " . $translations[$word] . ".";
        }
    }

    // === DEFINIZIONI RAPIDE ===
    $definitions = array(
        'pi greco' => 'Pi greco è circa 3.14159.',
        'euro' => 'L\'euro è la valuta ufficiale europea.',
        'raspberry pi' => 'È un piccolo computer a scheda singola.',
        'php' => 'PHP è un linguaggio di programmazione per il web.',
        'ai' => 'AI significa intelligenza artificiale.',
        'bluetooth' => 'Bluetooth è una tecnologia wireless a corto raggio.',
        'wifi' => 'Wi-Fi è una rete wireless.',
        'linux' => 'Linux è un sistema operativo open source.'
    );

    if (preg_match('/(?:cos[\'è]|cosa\s+è|che\s+cos[\'è])\s+(.+)/i', $q, $m)) {
        $term = strtolower(trim($m[1]));
        if (isset($definitions[$term])) {
            return $definitions[$term];
        }
    }

    // Numero random
    if (preg_match('/numero\s+(?:casuale|random)/i', $q)) {
        return "Numero casuale: " . rand(1, 100) . ".";
    }

    // Nessun match → Mistral Agent
    return null;
}

// ============================================================================
// EMOTIONAL INTELLIGENCE
// ============================================================================

function detect_emotion($question, $response) {
    $q_lower = strtolower($question);

    if (preg_match('/grazie|perfetto|ottimo|fantastico/i', $q_lower)) return 'cheerful';
    if (preg_match('/^(ciao|hey|salve)/i', $q_lower)) return 'friendly';
    if (preg_match('/perch[eé]|spiegami|come|cos[\'è]/i', $q_lower)) return 'thoughtful';
    if (preg_match('/aiuto|problema|errore|urgente/i', $q_lower)) return 'concerned';
    if (preg_match('/barzelletta|scherzo|divertente/i', $q_lower)) return 'playful';

    return 'natural';
}

// ============================================================================
// ELEVENLABS TTS
// ============================================================================

function call_elevenlabs($text, $config, $emotion = 'natural') {
    $voice_settings = array(
        'natural' => array('stability' => 0.5, 'similarity_boost' => 0.75, 'style' => 0.0),
        'cheerful' => array('stability' => 0.3, 'similarity_boost' => 0.75, 'style' => 0.8),
        'friendly' => array('stability' => 0.4, 'similarity_boost' => 0.8, 'style' => 0.5),
        'thoughtful' => array('stability' => 0.7, 'similarity_boost' => 0.75, 'style' => 0.3),
        'concerned' => array('stability' => 0.6, 'similarity_boost' => 0.7, 'style' => 0.4),
        'playful' => array('stability' => 0.3, 'similarity_boost' => 0.75, 'style' => 0.9),
    );

    $settings = $voice_settings[$emotion] ?? $voice_settings['natural'];
    $settings['use_speaker_boost'] = true;

    $data = array(
        'text' => $text,
        'model_id' => 'eleven_flash_v2_5',
        'output_format' => 'mp3_22050',
        'voice_settings' => $settings,
        'language_id' => 'it-IT'
    );

    $ch = curl_init('https://api.elevenlabs.io/v1/text-to-speech/' . $config['voice_id']);
    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER => array(
            'xi-api-key: ' . $config['elevenlabs_key'],
            'Content-Type: application/json'
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_FAILONERROR => false,
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("[ElevenLabs] HTTP $http_code: $curl_error");
        file_put_contents('/tmp/eleven_error.log', "HTTP $http_code\nError: $curl_error\nResponse: $response\n");
        return null;
    }

    if ($response === false || strlen($response) === 0) {
        error_log("[ElevenLabs] Empty or invalid audio response");
        return null;
    }

    return $response;
}

/**
 * Crea (una sola volta) il file audio statico per la radio,
 * usando ElevenLabs, e restituisce il path al file.
 */
function ensure_radio_intro_voice($config) {
    $file = $config['radio_intro_file'];

    // Se già esiste, usalo e basta
    if (file_exists($file)) {
        return $file;
    }

    // Assicurati che la cartella esista
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Testo super breve, per restare veloce
    $text = "Ok, metto la radio.";

    // Usiamo comunque ElevenLabs, ma UNA sola volta
    $audio_data = call_elevenlabs($text, $config, 'friendly');
    if (!$audio_data) {
        error_log("[Radio Intro] Impossibile generare la voce statica.");
        return null;
    }

    // Salva il file su disco
    if (file_put_contents($file, $audio_data) === false) {
        error_log("[Radio Intro] Impossibile scrivere il file: $file");
        return null;
    }

    error_log("[Radio Intro] Creato file statico: $file");
    return $file;
}

/**
 * Riproduce il file vocale statico prima della radio.
 */
function play_radio_intro_static($config) {
    $file = ensure_radio_intro_voice($config);
    if (!$file || !file_exists($file)) {
        return false;
    }

    $cmd = $config['player_cmd'] . ' -q ' . escapeshellarg($file) . ' >/dev/null 2>&1';
    exec($cmd, $out, $ret);

    if ($ret !== 0) {
        error_log("[Radio Intro] Errore nella riproduzione della voce statica (code $ret).");
        return false;
    }

    return true;
}


/**
 * Crea (una sola volta) il file audio statico per le news,
 * usando ElevenLabs, e restituisce il path al file.
 */
function ensure_news_intro_voice($config) {
    $file = $config['news_intro_file'];

    // Se già esiste, usalo
    if (file_exists($file)) {
        return $file;
    }

    // Assicurati che la cartella esista
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Testo breve per non sprecare TTS
    $text = "Ti leggo le ultime notizie.";

    $audio_data = call_elevenlabs($text, $config, 'thoughtful');
    if (!$audio_data) {
        error_log("[News Intro] Impossibile generare la voce statica.");
        return null;
    }

    if (file_put_contents($file, $audio_data) === false) {
        error_log("[News Intro] Impossibile scrivere il file: $file");
        return null;
    }

    error_log("[News Intro] Creato file statico: $file");
    return $file;
}

/**
 * Riproduce il file vocale statico prima delle news.
 */
function play_news_intro_static($config) {
    $file = ensure_news_intro_voice($config);
    if (!$file || !file_exists($file)) {
        return false;
    }

    $cmd = $config['player_cmd'] . ' -q ' . escapeshellarg($file) . ' >/dev/null 2>&1';
    exec($cmd, $out, $ret);

    if ($ret !== 0) {
        error_log("[News Intro] Errore nella riproduzione della voce statica (code $ret).");
        return false;
    }

    return true;
}





function write_log($config, $msg) {
    $dir = dirname($config['log_file']);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($config['log_file'], '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

// ============================================================================
// RADIO BROWSER API
// ============================================================================

function search_radio_browser($genre, $config) {
    $excluded_tags = array('talk', 'news', 'podcast', 'spoken', 'speech', 'radio news');

    $search_url = "https://de1.api.radio-browser.info/json/stations/search";
    $search_url .= "?tag=" . urlencode($genre);
    $search_url .= "&limit=10&hidebroken=true&order=clickcount&reverse=true";

    error_log("[Radio Browser] Searching by tag: $genre");

    $ch = curl_init($search_url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'DataPizza-Voice-v5.2',
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        error_log("[Radio Browser] Search failed - HTTP $http_code");
        return null;
    }

    $data = json_decode($response, true);

    if (empty($data) || !is_array($data)) {
        error_log("[Radio Browser] No results by tag, trying by name...");

        $search_url = "https://de1.api.radio-browser.info/json/stations/search";
        $search_url .= "?name=" . urlencode($genre);
        $search_url .= "&limit=10&hidebroken=true&order=clickcount&reverse=true";

        $ch = curl_init($search_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'DataPizza-Voice-v5.2',
            CURLOPT_SSL_VERIFYPEER => false
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
    }

    if (empty($data) || !is_array($data)) {
        error_log("[Radio Browser] No stations found for: $genre");
        return null;
    }

    $station = null;
    foreach ($data as $candidate) {
        $tags = strtolower($candidate['tags'] ?? '');
        $name = strtolower($candidate['name'] ?? '');

        $is_talk = false;
        foreach ($excluded_tags as $excluded) {
            if (stripos($tags, $excluded) !== false || stripos($name, $excluded) !== false) {
                $is_talk = true;
                error_log("[Radio Browser] Skipping talk radio: " . $candidate['name']);
                break;
            }
        }

        if (!$is_talk) {
            $station = $candidate;
            break;
        }
    }

    if (!$station) {
        error_log("[Radio Browser] No music-only stations found for: $genre");
        return null;
    }

    $station_name = $station['name'];
    $stream_url = $station['url_resolved'] ?? $station['url'];

    error_log("[Radio Browser] Found MUSIC station: $station_name");

    return array(
        'name' => $station_name,
        'url' => $stream_url,
        'id' => $station['stationuuid'],
        'bitrate' => $station['bitrate'] ?? 128
    );
}

function play_radio_stream($station, $config) {
    $pid_file = '/tmp/radio_pid.txt';

    if (file_exists($pid_file)) {
        error_log("[Radio] Stopping previous stream before starting new one");
        $old_pid = trim(file_get_contents($pid_file));
        exec("kill $old_pid 2>/dev/null");
        unlink($pid_file);
        usleep(500000);
    }

    $stream_url = $station['url_resolved'] ?? $station['url'];

    $cmd = "sh -c '{$config['player_cmd']} -q \"{$stream_url}\" > /dev/null 2>&1 & echo \$!'";
    $pid = exec($cmd);

    if (is_numeric($pid) && $pid > 0) {
        file_put_contents($pid_file, $pid);
        error_log("[Radio] Started playback - PID: $pid - URL: $stream_url");
        return true;
    }

    error_log("[Radio] Failed to start playback. Command: $cmd");
    return false;
}

function stop_radio() {
    $pid_file = '/tmp/radio_pid.txt';
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        exec("kill $pid 2>/dev/null");
        unlink($pid_file);
        error_log("[Radio] Stopped playback - PID: $pid");
        return true;
    }
    return false;
}

// ============================================================================
// NEWS & METEO FEED TOOLS
// ============================================================================

function load_last_news_state($config) {
    $file = $config['last_news_file'] ?? null;
    if (!$file) return [];

    if (!file_exists($file)) {
        return [];
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function save_last_news_state($config, $state) {
    $file = $config['last_news_file'] ?? null;
    if (!$file) return;

    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($file, json_encode($state));
}






function fetch_news_feed($config, $requested_category = null) {
    $url = $config['news_feed_url'];
	
	// DEBUG DNS/host
    $host = parse_url($url, PHP_URL_HOST);
    $ip   = gethostbyname($host);
    error_log("[News Feed] Host: $host -> IP: $ip");

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 20,
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_SSL_VERIFYPEER  => false,
        CURLOPT_SSL_VERIFYHOST  => 0,
        CURLOPT_USERAGENT       => 'DataPizza-Pi-Voice-v5.3'
    ));

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        error_log("[News Feed] HTTP $http_code: errore fetch URL=$url curl_err=$curl_err");
        return null;
    }

    $data = json_decode($response, true);
    if (!isset($data['items']) || !is_array($data['items'])) {
        error_log("[News Feed] JSON senza 'items' validi");
        return null;
    }

    // Normalizza categoria richiesta (slug del feed)
    $requested_category = $requested_category ? strtolower(trim($requested_category)) : null;

    // Stato: ultima news usata per global / categoria
    $state = load_last_news_state($config);
    $state_key = $requested_category ? "cat_" . $requested_category : "global";
    $last_audio = $state[$state_key] ?? null;

    $candidates = [];

    foreach ($data['items'] as $idx => $item) {
        $snippet   = $item['snippet']   ?? null;
        $audio_url = $item['audio_url'] ?? null;
        $category  = isset($item['category']) ? strtolower(trim($item['category'])) : '';

        // prendiamo solo news con audio + snippet
        if (empty($audio_url) || empty($snippet)) {
            continue;
        }

        // se è stata chiesta una categoria, filtra solo quella
        if ($requested_category !== null && $category !== $requested_category) {
            continue;
        }

        $candidates[] = [
            'idx'      => $idx,
            'item'     => $item,
            'category' => $category,
        ];
    }

    // Caso: categoria specifica ma nessun risultato → lascio che handle_local_tool dia il messaggio d'errore
    if ($requested_category !== null && empty($candidates)) {
        error_log("[News Feed] Nessuna news per categoria='{$requested_category}'");
        return null;
    }

    // Caso: nessuna categoria, ma ho candidati globali?
    if ($requested_category === null && empty($candidates)) {
        error_log("[News Feed] Nessuna news con audio_url valida trovata");
        return null;
    }

    // Evita ripetizione immediata: prova a escludere l'ultima audio_url
    $pool = [];

    foreach ($candidates as $cand) {
        $audio_url = $cand['item']['audio_url'] ?? null;
        if ($audio_url && $audio_url !== $last_audio) {
            $pool[] = $cand;
        }
    }

    // Se togliendo l'ultima non resta nulla (es. una sola news),
    // allora accettiamo che si ripeta.
    if (empty($pool)) {
        $pool = $candidates;
    }

    // Scelta RANDOM dal pool
    $chosen_idx = array_rand($pool);
    $chosen     = $pool[$chosen_idx];

    $item = $chosen['item'];
    $cat  = $chosen['category'] ?: 'n.d.';

    if ($requested_category !== null) {
        error_log("[News Feed] News RANDOM categoria='{$requested_category}' idx={$chosen['idx']} category=$cat");
    } else {
        error_log("[News Feed] News RANDOM globale idx={$chosen['idx']} category=$cat");
    }

    // Aggiorna stato ultimo audio usato
    if (!empty($item['audio_url'])) {
        $state[$state_key] = $item['audio_url'];
        save_last_news_state($config, $state);
    }

    return [
        'summary'   => $item['snippet'],
        'audio_url' => $item['audio_url'],
    ];
}



/**
 * Normalizza la categoria richiesta dall'utente
 * in uno slug coerente con il feed finale.
 *
 * Categorie disponibili: tech, finanza, arte, sport, intrattenimento, meteo
 */
function normalize_news_category($raw) {
    $txt = strtolower(trim($raw));

    // Togli articoli/preposizioni in eccesso
    $txt = preg_replace('/\b(di|della|delle|degli|dei|del|sulla|sulle|sugli|sui|su)\b/i', '', $txt);
    $txt = trim($txt);

    $map = [
        // FINANZA
        'finanza'      => 'finanza',
        'economia'     => 'finanza',
        'borsa'        => 'finanza',
        'mercati'      => 'finanza',

        // TECH
        'tech'         => 'tech',
        'tecnologia'   => 'tech',
        'innovazione'  => 'tech',
        'startup'      => 'tech',

        // ARTE
        'arte'         => 'arte',
        'musei'        => 'arte',
        'mostre'       => 'arte',

        // SPORT
        'sport'        => 'sport',
        'calcio'       => 'sport',
        'basket'       => 'sport',
        'tennis'       => 'sport',

        // INTRATTENIMENTO
        'intrattenimento' => 'intrattenimento',
        'spettacolo'      => 'intrattenimento',
        'cinema'          => 'intrattenimento',
        'serie tv'        => 'intrattenimento',
        'musica'          => 'intrattenimento',

        // METEO – teoricamente useremo il widget dedicato,
        // ma lo mappo per completezza
        'meteo'        => 'meteo',
        'tempo'        => 'meteo',
        'previsioni'   => 'meteo',
    ];

    return $map[$txt] ?? null;
}



function fetch_meteo_data($config) {
    $url = $config['news_feed_url'];
	
	
	

    error_log("[Meteo Tool] Fetching da: $url");

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'DataPizza-Pi-Voice-v5.2'
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("[Meteo Tool] HTTP $http_code - CURL Error: $curl_error");
        return null;
    }

    error_log("[Meteo Tool] HTTP 200 OK - Response length: " . strlen($response) . " bytes");

    $data = json_decode($response, true);
    if ($data === null) {
        error_log("[Meteo Tool] JSON decode FAILED - Error: " . json_last_error_msg());
        error_log("[Meteo Tool] Raw response (first 200 chars): " . substr($response, 0, 200));
        return null;
    }

    error_log("[Meteo Tool] JSON decoded OK - Items count: " . (isset($data['items']) ? count($data['items']) : 0));

    if (!empty($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $index => $item) {
            $item_category = isset($item['category']) ? trim(strtolower($item['category'])) : '';

            error_log("[Meteo Tool] Item #$index - Category: '$item_category' - Has data: " . (isset($item['data']) ? 'YES' : 'NO'));

            if ($item_category === 'meteo' && isset($item['data'])) {
                error_log("[Meteo Tool] MATCH TROVATO! Restituisco dati meteo.");
                return $item['data'];
            }
        }
    } else {
        error_log("[Meteo Tool] 'items' array vuoto o non esistente");
    }

    error_log("[Meteo Tool] Nessun widget 'meteo' trovato nel feed");
    return null;
}

function fetch_and_stream_external_audio($url, $config) {
    echo "  [STREAM] Esecuzione stream audio esterno (curl+pipe): $url\n";
    $start_stream = microtime(true);

    $player_cmd = 'curl -s -L "' . $url . '" | ' . $config['player_cmd'] . ' -q -';
    exec($player_cmd, $output, $return_var);

    if ($return_var !== 0) {
        error_log("[ERROR] Player command failed with code $return_var (curl/mpg123 pipe) for URL: $url");
        return 0;
    }

    $stream_time = round((microtime(true) - $start_stream) * 1000);
    echo "  [STREAM] Riproduzione esterna completata in {$stream_time} ms.\n";
    return $stream_time;
}

// ============================================================================
// PROCESS COMMAND
// ============================================================================

function process_command($question, $config) {
    $start = microtime(true);

    echo "\n\t📝 Domanda: \"$question\"\n";

    $ai_response = null;
    $llm_time = 0;
    $source = "UNKNOWN";

    // Step 1: Cache
    $cached = get_cached_response($question, $config);
    if ($cached) {
        echo "\t💾 Cache hit!\n";

        if (is_news_tool_response($cached)) {
            echo "\t⚠️ Cache News invalidata (URL dinamico/scaduto). Nuovo fetch.\n";
            $cached = null;
        }

        if ($cached) {
            $preview = is_string($cached)
                ? (strlen($cached) > 60 ? substr($cached, 0, 57) . '...' : $cached)
                : '[ARRAY]';
            echo "\t\t✅ Risposta: \"$preview\"\n";
            $ai_response = $cached;
            $llm_time = 0;
            $source = "CACHE";
        }
    }

    // Step 2: Local tools / Agent
    if (!$cached) {
        $local_response = handle_local_tool($question);

        if ($local_response) {

            // NEWS AUDIO FLOW
           // ⚡ GESTIONE SPECIALE NEWS AUDIO (NUOVO FLUSSO) ⚡
// ⚡ GESTIONE SPECIALE NEWS AUDIO (PREFETCH + PLAY) ⚡
// ⚡ GESTIONE SPECIALE NEWS AUDIO (PREFETCH + PLAY) ⚡
// ⚡ GESTIONE SPECIALE NEWS AUDIO - STREAM DIRETTO ⚡
if (is_news_tool_response($local_response)) {
    $ai_response = $local_response;
    $source = "LOCAL";
    $llm_time = round((microtime(true) - $start) * 1000);

    echo "  ⚡ Local Tool News (Audio Esterno) attivato.\n";

    $audio_url = $ai_response['audio_url'];
    echo "  [DEBUG] URL MP3: " . $audio_url . "\n";

    // 1) Intro statica delle news
    echo "  [1/2] 🔊 Intro news statica...\n";
    $intro_tts_start = microtime(true);
    $intro_ok        = play_news_intro_static($config);
    $intro_tts_time  = round((microtime(true) - $intro_tts_start) * 1000);

    if ($intro_ok) {
        echo "  ✅ Intro News statica: {$intro_tts_time}ms\n";
    } else {
        echo "  ⚠️ Intro News non riprodotta, passo direttamente all'audio esterno.\n";
    }

    // 2) Streaming diretto dell'audio remoto (curl | mpg123 -)
    echo "  [2/2] 🔊 Avvio streaming news...\n";
    $stream_time = fetch_and_stream_external_audio($audio_url, $config);

    $total = round((microtime(true) - $start) * 1000);
    echo "\n\t📊 TOTALE News Stream: {$total}ms\n";
    echo str_repeat("─", 64) . "\n";

    return true; // Fine del processo per il flusso News
}





            // RADIO STREAM FAST-PATH
            // ⚡ GESTIONE SPECIALE RADIO STREAM ⚡
if (is_radio_stream_tool_response($local_response)) {
    $ai_response = $local_response;
    $source = "LOCAL";
    $llm_time = round((microtime(true) - $start) * 1000);

    echo "  ⚡ Local Tool Radio (STATIC INTRO + Stream) attivato.\n";

    // 1) Intro statica da file (zero TTS runtime)
    echo "  [1/2] 🔊 Intro radio statica...\n";
    $intro_start = microtime(true);
    $intro_ok = play_radio_intro_static($config);
    $intro_time = round((microtime(true) - $intro_start) * 1000);
    if ($intro_ok) {
        echo "  ✅ Intro Radio statica: {$intro_time}ms\n";
    } else {
        echo "  ⚠️ Intro Radio non riprodotta (uso solo stream).\n";
    }

    // 2) Avvia lo streaming radio SOLO DOPO l'intro
    echo "  [2/2] 🎵 Avvio streaming radio...\n";
    $station = $ai_response['station'];
    $started = play_radio_stream($station, $config);

    if (!$started) {
        echo "  ❌ Impossibile avviare lo streaming radio.\n";
    }

    $total = round((microtime(true) - $start) * 1000);
    echo "\n  📊 TOTALE Radio Stream: {$total}ms\n";
    echo str_repeat("─", 64) . "\n";
    return true; // IMPORTANTE: chiudiamo qui il flusso Radio
}


            // Local tool semplice
            echo "\t⚡ Tool locale\n";
            $preview = is_string($local_response)
                ? (strlen($local_response) > 60 ? substr($local_response, 0, 57) . '...' : $local_response)
                : '[ARRAY]';
            echo "\t\t✅ Risposta: \"$preview\"\n";

            $ai_response = $local_response;
            $llm_time = round((microtime(true) - $start) * 1000);
            $source = "LOCAL";

           // NON mettiamo MAI in cache le news
if (is_news_tool_response($local_response)) {
    // Skip cache
} else {
    cache_response($question, $local_response, $config);
}

        } else {
            // Mistral Agent
            if (!check_daily_limit($config)) {
                echo "\t\t⚠️ Limite giornaliero raggiunto!\n";
                $ai_response = "Limite giornaliero Mistral raggiunto.";
                $llm_time = 0;
                $source = "LIMIT";
            } else {
                echo "\t[1/2] 🇫🇷 Mistral Agent...\n";

                $agent_id = get_agent_id($config);
                if (!$agent_id) {
                    echo "\t\t❌ Errore agent creation\n";
                    return false;
                }

                $llm_start = microtime(true);
                $ai_response = send_to_agent($agent_id, $question, $config);

                if (!$ai_response) {
                    echo "\t\t❌ Errore LLM\n";
                    return false;
                }

                $llm_time = round((microtime(true) - $llm_start) * 1000);
                $preview = strlen($ai_response) > 60 ? substr($ai_response, 0, 57) . '...' : $ai_response;
                echo "\t\t✅ Agent ({$llm_time}ms): \"$preview\"\n";

                $source = "AGENT";
                cache_response($question, $ai_response, $config);
                increment_daily_limit($config);
            }
        }
    }

    // Logging
    if (is_array($ai_response)) {
        $log_response = $ai_response['type'] ?? 'ARRAY';
    } else {
        $log_response = $ai_response;
    }
    write_log($config, "$source: $question → $log_response ({$llm_time}ms)");

    // Se è array (News/Radio) saremmo già usciti sopra
    if (is_array($ai_response)) {
        return true;
    }

    // Step 3: TTS standard
    $char_count = strlen($ai_response);
    $credits_needed = round($char_count * 0.5, 1);

    echo "\n\t[2/2] 🇬🇧 ElevenLabs TTS (~$credits_needed credits)...\n";

    $emotion = detect_emotion($question, $ai_response);
    $emotion_icons = array(
        'natural' => '😐', 'cheerful' => '😊', 'friendly' => '👋',
        'thoughtful' => '🤔', 'concerned' => '😟', 'playful' => '😄'
    );
    echo "\t\t🎭 " . ($emotion_icons[$emotion] ?? '🎭') . " $emotion\n";

    $tts_start = microtime(true);
    $audio_data = call_elevenlabs($ai_response, $config, $emotion);

    if (!$audio_data) {
        echo "\t\t⚠️ Errore TTS (skip audio)\n";
        $total = round((microtime(true) - $start) * 1000);
        echo "\n\t📊 TOTALE: {$total}ms (no audio)\n";
        return true;
    }

    $bytes = strlen($audio_data);
    $tts_time = round((microtime(true) - $tts_start) * 1000);

    echo "\t\t✅ TTS ({$tts_time}ms): " . round($bytes / 1024, 1) . " KB (in RAM)\n";

    echo "\n\t🔊 Riproduzione tramite Pipe...\n";
    $play_start = microtime(true);

    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

    $process = proc_open($config['player_cmd'] . ' -q -', $descriptorspec, $pipes);

    if (is_resource($process)) {
        fwrite($pipes[0], $audio_data);
        fclose($pipes[0]);

        stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        if (!empty($stderr)) {
            error_log("[mpg123 Pipe Error] $stderr");
        }
        fclose($pipes[2]);

        proc_close($process);
    } else {
        error_log("Impossibile aprire il player: " . $config['player_cmd']);
    }

    $play_time = round((microtime(true) - $play_start) * 1000);
    $total = round((microtime(true) - $start) * 1000);

    echo "\n\t📊 Performance:\n";
    echo "\t\tSource: $source\n";
    echo "\t\tLLM:\t{$llm_time}ms\n";
    echo "\t\tTTS:\t{$tts_time}ms\n";
    echo "\t\tAudio:\t{$play_time}ms\n";
    echo "\t\t🎯 TOTALE: {$total}ms\n";

    if ($total < 10000) {
        echo "\t\t🏆 <10s ECCELLENTE!\n";
    } elseif ($total < 12000) {
        echo "\t\t✅ <12s target OK!\n";
    } elseif ($total < 15000) {
        echo "\t\t⚠️ <15s accettabile\n";
    }

    if ($source === "AGENT") {
        echo "\t\t💰 ~$credits_needed crediti Mistral\n";
    } else {
        echo "\t\t💰 0 crediti Mistral!\n";
    }

    return true;
}

// ============================================================================
// BANNER
// ============================================================================

system('clear');

echo "\033[1;36m";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║ 🍕 DATAPIZZA v5.3 FAST RADIO - EUROPEAN AGENTS 🇪🇺🛠️           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\033[0m\n";

echo "\033[1;32m🇪🇺 100% EUROPEAN AI STACK:\033[0m\n";
echo "\t🇬🇧 Raspberry Pi Model B 2011 (Cambridge, UK)\n";
echo "\t🇫🇷 Mistral Agents API (Paris, France)\n";
echo "\t🇬🇧 ElevenLabs Turbo v2.5 (London, UK)\n";
echo "\t🇮🇹 DataPizza Framework (Cagliari, Italy)\n\n";

echo "\033[1;33m📊 Hardware:\033[0m " . php_uname('m') . " | PHP " . PHP_VERSION . "\n";
echo "\033[1;32m🎤 Wake word:\033[0m 'hey datapizza'\n";
echo "\033[1;32m⚡ LLM:\033[0m {$CONFIG['mistral_model']}\n";
echo "\033[1;35m🎭 TTS:\033[0m ElevenLabs Turbo v2.5\n";
echo "\033[1;35m🔊 Player:\033[0m PIPE (mpg123 -)\n\n";

echo "\033[1;32m🚀 v5.3 FAST IMPROVEMENTS:\033[0m\n";
echo "\t✓ Radio: nessuna chiamata TTS, start immediato\n";
echo "\t✓ News: audio esterno via curl|mpg123\n";
echo "\t✓ Local tools: risposte più brevi\n";
echo "\t✓ Cache risposte e daily limit Mistral\n\n";

$agent_exists = file_exists($CONFIG['agent_id_file']);

if ($agent_exists) {
    $agent_id = trim(file_get_contents($CONFIG['agent_id_file']));
    echo "\033[1;35m📝 Agent esistente:\033[0m " . substr($agent_id, 0, 25) . "...\n\n";
} else {
    echo "\033[1;33m🔧 Prima esecuzione:\033[0m\n";
    echo "\t→ Creerà agent Mistral al primo comando\n\n";
}

if (file_exists($CONFIG['daily_limit_file'])) {
    $data = json_decode(file_get_contents($CONFIG['daily_limit_file']), true);
    $today = date('Y-m-d');
    $count = isset($data[$today]) ? $data[$today] : 0;
    echo "\033[1;33m💰 Chiamate Mistral oggi:\033[0m $count / {$CONFIG['daily_mistral_limit']}\n\n";
}

echo str_repeat("─", 64) . "\n";
echo "\033[1;32m🔊 In ascolto... (Ctrl+C per fermare)\033[0m\n";
echo str_repeat("─", 64) . "\n\n";

// ============================================================================
// MAIN LOOP
// ============================================================================

$last_mtime = 0;
$last_cmd = time();

while (true) {
    if (file_exists($CONFIG['trigger_file'])) {
        $mtime = filemtime($CONFIG['trigger_file']);

        if ($mtime > $last_mtime) {
            $question_raw = file_get_contents($CONFIG['trigger_file']);
            $question = str_ireplace($CONFIG['wake_word'], '', trim($question_raw));

            if (!empty($question)) {
                $last_mtime = $mtime;
                $last_cmd = time();

                process_command($question, $CONFIG);
            }
        }
    }

    $cooldown_passed = (time() - $last_cmd) >= $CONFIG['cooldown_time'];
    usleep($cooldown_passed ? $CONFIG['poll_interval'] : 500000);
}
