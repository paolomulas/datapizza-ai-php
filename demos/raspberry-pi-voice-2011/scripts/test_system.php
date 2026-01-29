<?php
/**
 * System Requirements Test Script
 * Tests if system is ready for voice assistant
 */

echo "🔍 Testing system requirements...\n\n";

// Test PHP version
echo "PHP Version: " . PHP_VERSION;
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo " ✅\n";
} else {
    echo " ❌ (requires 8.0+)\n";
}

// Test required extensions
$required_extensions = array('curl', 'sockets', 'posix');
foreach ($required_extensions as $ext) {
    echo "Extension $ext: ";
    echo extension_loaded($ext) ? "✅\n" : "❌\n";
}

// Test audio system
echo "\n🔊 Audio System:\n";
echo "PulseAudio: ";
$pa_check = shell_exec("pactl info 2>&1");
echo (strpos($pa_check, 'Server Name:') !== false) ? "✅\n" : "❌\n";

echo "Bluetooth: ";
$bt_check = shell_exec("which bluetoothctl 2>&1");
echo $bt_check ? "✅\n" : "❌\n";

echo "\n✨ System test complete!\n";
?>