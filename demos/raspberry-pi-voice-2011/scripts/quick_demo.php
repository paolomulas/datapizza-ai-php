<?php
/**
 * Quick Demo - Test rapidi voice assistant
 */

$commands = array(
    '1' => 'ciao come stai?',
    '2' => 'quanto fa 127 per 43?',
    '3' => 'raccontami una barzelletta',
    '4' => 'chi ha inventato la lampadina?',
    '5' => 'traduci in inglese: la vita è bella',
    'q' => 'QUIT'
);

$trigger = '/tmp/datapizza_voice.txt';
$wake = 'hey datapizza';

echo "🍕 Quick Demo Commands:\n\n";
foreach ($commands as $key => $cmd) {
    echo "[$key] $cmd\n";
}

echo "\nSeleziona (1-5, q=quit): ";
$input = trim(fgets(STDIN));

if ($input === 'q') {
    echo "Ciao!\n";
    exit(0);
}

if (isset($commands[$input])) {
    $full = $wake . ' ' . $commands[$input];
    echo "\n✅ Invio: \"$full\"\n";
    file_put_contents($trigger, $full);
    echo "📡 Controlla il terminale monitor!\n";
} else {
    echo "❌ Selezione non valida\n";
}
?>
