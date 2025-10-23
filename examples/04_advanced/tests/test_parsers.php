<?php
require_once __DIR__ . '/../datapizza/modules/parsers/text_parser.php';
require_once __DIR__ . '/../datapizza/modules/parsers/json_parser.php';
require_once __DIR__ . '/../datapizza/modules/parsers/csv_parser.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 DataPizza - Parsers Demo                   ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Crea file di test
$test_dir = __DIR__ . '/../data/test_parsers/';
if (!is_dir($test_dir)) {
    mkdir($test_dir, 0755, true);
}

// 1. Test TXT
echo "📝 Test 1: Text Parser\n";
echo str_repeat("═", 54) . "\n";
file_put_contents($test_dir . 'doc.txt', "Questo è un documento di test.\nCon più righe.");
$txt = parser_parse_text($test_dir . 'doc.txt');
echo "Parsed: {$txt['metadata']['filename']}\n";
echo "Text: " . substr($txt['text'], 0, 50) . "...\n\n";

// 2. Test JSON
echo "📝 Test 2: JSON Parser\n";
echo str_repeat("═", 54) . "\n";
$json_data = array(
    array('title' => 'Doc 1', 'content' => 'Contenuto doc 1'),
    array('title' => 'Doc 2', 'content' => 'Contenuto doc 2')
);
file_put_contents($test_dir . 'docs.json', json_encode($json_data));
$json = parser_parse_json($test_dir . 'docs.json', 'content');
echo "Parsed: {$json['metadata']['filename']}\n";
echo "Items: {$json['metadata']['items']}\n";
echo "Text: " . substr($json['text'], 0, 50) . "...\n\n";

// 3. Test CSV
echo "📝 Test 3: CSV Parser\n";
echo str_repeat("═", 54) . "\n";
$csv_content = "name,description\nTool1,Descrizione tool 1\nTool2,Descrizione tool 2";
file_put_contents($test_dir . 'tools.csv', $csv_content);
$csv = parser_parse_csv($test_dir . 'tools.csv');
echo "Parsed: {$csv['metadata']['filename']}\n";
echo "Rows: {$csv['metadata']['rows']}\n";
echo "Text preview:\n" . substr($csv['text'], 0, 80) . "...\n\n";

echo "✅ Tutti i parser funzionanti!\n";
echo "\n💡 Ora puoi fare ingestion da file esterni!\n";
