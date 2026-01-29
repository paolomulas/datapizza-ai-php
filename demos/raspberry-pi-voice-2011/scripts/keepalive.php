<?php
/**
 * Bluetooth Speaker Keep-Alive Service
 * Prevents Bluetooth speakers from auto-sleep by playing periodic silence
 * 
 * Educational Purpose:
 * Shows how to generate WAV files programmatically and keep audio devices active.
 * Demonstrates PCM audio format and PulseAudio integration.
 * 
 * Hardware: Any Raspberry Pi with Bluetooth
 * CPU Usage: <1% (plays 1s audio every 10 minutes)
 * Battery Impact: ~1-2% per hour
 * 
 * @author Paolo Mulas
 * @version 1.0
 */

// ============================================================================
// CONFIGURAZIONE
// ============================================================================

$CONFIG = array(
    // Timing
    'interval_seconds' => 600,          // 10 minuti tra keep-alive
    'silence_duration' => 1,            // 1 secondo di silenzio
    
    // Audio format
    'sample_rate' => 16000,             // 16kHz (basso = meno CPU)
    'channels' => 1,                    // Mono (stereo non serve)
    'bits_per_sample' => 16,            // 16-bit PCM
    
    // File paths
    'silence_file' => __DIR__ . '/../../data/voice/silence.wav',
    'log_file' => __DIR__ . '/../../data/logs/keepalive.log',
    
    // Audio player
    'player_cmd' => 'paplay',
    
    // Logging
    'enable_logging' => true,
    'log_every_n' => 6                  // Log ogni 6 keep-alive (1 ora)
);

// ============================================================================
// FUNZIONI
// ============================================================================

/**
 * Genera file WAV con silenzio digitale
 * 
 * Educational note: WAV format structure
 * - Header: 44 bytes (metadata)
 * - Data: PCM samples (16-bit integers, 0 = silence)
 * 
 * Reference: http://soundfile.sapp.org/doc/WaveFormat/
 */
function generate_silence_wav($config) {
    $sample_rate = $config['sample_rate'];
    $channels = $config['channels'];
    $duration = $config['silence_duration'];
    $bits = $config['bits_per_sample'];
    
    // Calcola dimensioni
    $num_samples = $sample_rate * $duration * $channels;
    $data_size = $num_samples * ($bits / 8);
    $file_size = $data_size + 36;
    
    // WAV Header (44 bytes)
    $header = pack(
        'a4Va4a4VvvVVvva4V',
        'RIFF',                                      // ChunkID
        $file_size,                                  // ChunkSize
        'WAVE',                                      // Format
        'fmt ',                                      // Subchunk1ID
        16,                                          // Subchunk1Size
        1,                                           // AudioFormat (1=PCM)
        $channels,                                   // NumChannels
        $sample_rate,                                // SampleRate
        $sample_rate * $channels * ($bits / 8),     // ByteRate
        $channels * ($bits / 8),                    // BlockAlign
        $bits,                                       // BitsPerSample
        'data',                                      // Subchunk2ID
        $data_size                                   // Subchunk2Size
    );
    
    // Genera dati silenziosi (tutti zeri)
    $silence_data = str_repeat(pack('v', 0), $num_samples);
    
    // Assicura directory esista
    $dir = dirname($config['silence_file']);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Scrivi file
    file_put_contents($config['silence_file'], $header . $silence_data);
}

/**
 * Formatta uptime in formato leggibile
 */
function format_uptime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%dh %dm', $hours, $minutes);
    } elseif ($minutes > 0) {
        return sprintf('%dm %ds', $minutes, $secs);
    } else {
        return sprintf('%ds', $secs);
    }
}

/**
 * Log keep-alive activity
 */
function write_log($config, $message) {
    if (!$config['enable_logging']) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    $log_dir = dirname($config['log_file']);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($config['log_file'], $log_entry, FILE_APPEND | LOCK_EX);
}

// ============================================================================
// BANNER INIZIALE
// ============================================================================

system('clear');

echo "\033[1;36m";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║      🔵 Bluetooth Speaker Keep-Alive Service 🔵          ║\n";
echo "║         Raspberry Pi Model B (2011) Edition               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\033[0m\n";

echo "\033[1;33m📊 Configurazione:\033[0m\n";
echo "   Intervallo: " . ($CONFIG['interval_seconds'] / 60) . " minuti\n";
echo "   Durata silenzio: {$CONFIG['silence_duration']}s\n";
echo "   Sample rate: {$CONFIG['sample_rate']} Hz (Mono)\n";
echo "   File: " . basename($CONFIG['silence_file']) . "\n\n";

// ============================================================================
// GENERA FILE SILENZIO
// ============================================================================

if (!file_exists($CONFIG['silence_file'])) {
    echo "🔧 Generazione file silenzio WAV...\n";
    generate_silence_wav($CONFIG);
    
    $size = filesize($CONFIG['silence_file']);
    echo "   ✅ File creato: " . round($size / 1024, 1) . " KB\n\n";
} else {
    echo "✅ File silenzio esistente\n\n";
}

// ============================================================================
// VERIFICA SISTEMA AUDIO
// ============================================================================

echo "🔍 Verifica sistema audio...\n";

// Check PulseAudio
$pa_check = shell_exec("pactl info 2>&1");
if (strpos($pa_check, 'Server Name:') !== false) {
    echo "   ✅ PulseAudio: attivo\n";
} else {
    echo "   ❌ PulseAudio: non trovato\n";
    echo "   Esegui: pulseaudio --start\n";
    exit(1);
}

// Check Bluetooth sink
$bt_check = shell_exec("pactl list sinks short 2>&1");
if (strpos($bt_check, 'bluez') !== false) {
    echo "   ✅ Bluetooth sink: rilevato\n\n";
} else {
    echo "   ⚠️  Bluetooth sink: non rilevato\n";
    echo "   (Continuo comunque - verrà rilevato quando connetti speaker)\n\n";
}

// ============================================================================
// EDUCATIONAL NOTE
// ============================================================================

echo "\033[1;35m🎓 Educational Note:\033[0m\n";
echo "   Gli speaker Bluetooth vanno in sleep dopo 10-20 minuti di silenzio\n";
echo "   per risparmio energetico (regolamento EU). Questo servizio previene\n";
echo "   lo sleep riproducendo 1s di silenzio digitale ogni " . ($CONFIG['interval_seconds'] / 60) . " minuti.\n\n";

echo "\033[1;33m💡 Perché Funziona:\033[0m\n";
echo "   • Il silenzio è PCM a zeri (no rumore, ma valido come audio)\n";
echo "   • Lo speaker rileva attività → reset timer auto-sleep\n";
echo "   • CPU usage: <1% (1s audio ogni " . ($CONFIG['interval_seconds'] / 60) . "min)\n";
echo "   • Battery impact: ~1-2% aggiuntivo per ora\n\n";

echo "\033[1;32m💪 Raccomandazione:\033[0m\n";
echo "   Per demo o uso continuo, tieni lo speaker connesso a USB power.\n";
echo "   Keep-alive è utile come backup se si scollega l'alimentazione.\n\n";

echo str_repeat("─", 60) . "\n";
echo "\033[1;32m🔊 Servizio avviato - Ctrl+C per fermare\033[0m\n";
echo str_repeat("─", 60) . "\n\n";

// ============================================================================
// LOOP PRINCIPALE
// ============================================================================

$loop_count = 0;
$start_time = time();

// Signal handling per shutdown graceful
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() use ($CONFIG, $loop_count, $start_time) {
        $uptime = time() - $start_time;
        echo "\n\n\033[1;31m🛑 Servizio fermato\033[0m\n";
        echo "   Keep-alive inviati: $loop_count\n";
        echo "   Uptime: " . format_uptime($uptime) . "\n";
        write_log($CONFIG, "Servizio fermato - $loop_count keep-alive - uptime: " . format_uptime($uptime));
        exit(0);
    });
}

while (true) {
    if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
    }
    
    $loop_count++;
    $uptime_sec = time() - $start_time;
    $uptime = format_uptime($uptime_sec);
    
    $timestamp = date('H:i:s');
    
    echo "\033[1;36m[$timestamp]\033[0m 🔔 Keep-alive #$loop_count \033[0;90m(uptime: $uptime)\033[0m\n";
    
    // Riproduci file silenzio
    $play_cmd = $CONFIG['player_cmd'] . ' ' . escapeshellarg($CONFIG['silence_file']) . ' 2>&1';
    $output = shell_exec($play_cmd);
    
    // Controlla errori
    if ($output && (stripos($output, 'error') !== false || stripos($output, 'failed') !== false)) {
        echo "   \033[0;33m⚠️  Warning:\033[0m $output\n";
        write_log($CONFIG, "ERROR: $output");
    } else {
        echo "   \033[1;32m✅ Keep-alive inviato con successo\033[0m\n";
        
        // Log periodico (ogni N keep-alive per non riempire log)
        if ($CONFIG['enable_logging'] && ($loop_count % $CONFIG['log_every_n'] === 0)) {
            write_log($CONFIG, "Keep-alive #$loop_count - Uptime: $uptime");
        }
    }
    
    // Calcola prossimo evento
    $next_timestamp = time() + $CONFIG['interval_seconds'];
    $next_time = date('H:i:s', $next_timestamp);
    $wait_min = round($CONFIG['interval_seconds'] / 60);
    
    echo "   \033[0;90m⏰ Prossimo keep-alive: $next_time (tra ${wait_min}m)\033[0m\n\n";
    
    // Sleep fino al prossimo intervallo
    sleep($CONFIG['interval_seconds']);
}
?>
