<?php
//require_once __DIR__ . '/../datapizza/clients/openai.php';
require_once __DIR__ . '/../datapizza/clients/deepseek.php';  // ✅ Cambiato!
// Carica tutte le variabili dal file .env come variabili d'ambiente
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

/* // Test con parametri migliorati
echo "=== Test OpenAI Client ===\n\n";
echo "Risposta: " . openai_complete("Scrivi una breve barzelletta divertente su PHP.", 150, 0.8) . "\n"; */




echo "=== Test DeepSeek Client ===\n\n";
echo "Risposta: " . deepseek_complete("Scrivi una breve barzelletta divertente su PHP.", 150, 0.8) . "\n";
?>


