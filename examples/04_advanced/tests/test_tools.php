<?php
require_once __DIR__ . '/../datapizza/tools/calculator.php';
require_once __DIR__ . '/../datapizza/tools/datetime_tool.php';
require_once __DIR__ . '/../datapizza/tools/file_reader.php';

echo "=== Test Tools ===\n\n";

// Test 1: Calculator
echo "1. Test Calculator:\n";
$calc = new Calculator();
echo "  Nome: " . $calc->get_name() . "\n";
echo "  Descrizione: " . $calc->get_description() . "\n";
echo "  Calcolo: 15 + 27 = " . $calc->execute(['expression' => '15 + 27']) . "\n";
echo "  Calcolo: (100 - 20) / 4 = " . $calc->execute(['expression' => '(100 - 20) / 4']) . "\n\n";

// Test 2: DateTime
echo "2. Test DateTime:\n";
$dt = new DateTimeTool();
echo "  Data corrente: " . $dt->execute(['action' => 'current']) . "\n";
echo "  Data formattata: " . $dt->execute(['action' => 'format', 'date' => '2025-10-14', 'format' => 'd/m/Y']) . "\n";
echo "  Differenza date: " . $dt->execute([
    'action' => 'diff',
    'date1' => '2025-10-01',
    'date2' => '2025-10-14'
]) . "\n\n";

// Test 3: File Reader
echo "3. Test File Reader:\n";
$fr = new FileReader();

// Crea un file di test
file_put_contents(__DIR__ . '/../data/test_file.txt', "Questo è un file di test!\nRiga 2\nRiga 3");

echo "  Lettura file: \n";
echo $fr->execute(['filename' => 'test_file.txt']) . "\n\n";

echo "✅ Test completato!\n";
