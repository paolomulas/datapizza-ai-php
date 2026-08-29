<?php
/**
 * 🍕 Example: Sysadmin Agent - AI-Powered Server Monitoring
 * 
 * This file composes three reviewed, read-only inspection tools. It remains an
 * educational example: provider configuration and an explicitly selected demo
 * log are required, and the tools do not authorize production operations.
 * 
 * Created for ADMIN Magazine article on Datapizza-AI-PHP.
 * 
 * Security note: read-only behavior does not make observations non-sensitive.
 * Use only deliberately approved paths and non-sensitive demonstration data.
 */

require_once __DIR__ . '/../../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../../datapizza/tools/disk_space.php';
require_once __DIR__ . '/../../datapizza/tools/system_uptime.php';
require_once __DIR__ . '/../../datapizza/tools/log_grep.php';

// Load environment variables only after all local dependencies are present.
$envFile = __DIR__ . '/../../.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "Missing provider configuration: create .env in the repository root.\n");
    exit(1);
}

$env = parse_ini_file($envFile);
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

$demoLogPath = getenv('SYSADMIN_DEMO_LOG');
if (!$demoLogPath || !is_file($demoLogPath)) {
    fwrite(STDERR, "Set SYSADMIN_DEMO_LOG to an approved readable .log or .txt file.\n");
    exit(1);
}
$demoLogDirectory = dirname(realpath($demoLogPath));
$demoLogFilename = basename($demoLogPath);

echo "=== 🛠️  Sysadmin Agent Test ===\n";
echo "Demonstrating the reviewed sysadmin tool composition\n\n";

// Step 1: Initialize sysadmin tools
$tools = [
    new DiskSpaceTool(['/']),
    new SystemUptimeTool(),
    new LogGrepTool($demoLogDirectory)
];

// Step 2: Create the ReAct agent
// verbose=true shows you the agent's reasoning process
$agent = new ReactAgent(
    'openai',
    'gpt-4o-mini',
    $tools,
    5,  // Max reasoning iterations
    true // Educational mode - see the thinking!
);

// Test 1: Basic health check
echo "📊 Test 1: System Health Check\n";
echo str_repeat("=", 60) . "\n";
$response = $agent->run("Check the system health: disk space and uptime.");
echo "\n🎯 Final answer:\n$response\n\n";

// Test 2: Disk space analysis
echo "\n💾 Test 2: Disk Space Analysis\n";
echo str_repeat("=", 60) . "\n";
$response = $agent->run("Is the root filesystem getting full? Check disk usage.");
echo "\n🎯 Final answer:\n$response\n\n";

// Test 3: Log analysis against an explicitly configured test file
echo "\n📋 Test 3: Log Analysis\n";
echo str_repeat("=", 60) . "\n";
$response = $agent->run("Search for authentication failures (pattern 'Failed') in {$demoLogFilename}.");
echo "\n🎯 Final answer:\n$response\n\n";


// Test 4: Multi-step reasoning
echo "\n🧠 Test 4: Complex Query\n";
echo str_repeat("=", 60) . "\n";
$response = $agent->run(
    "Give me a server health report: check uptime, disk space, and look for authentication failures " .
    "(pattern 'Failed') in {$demoLogFilename}."
);

echo "\n🎯 Final answer:\n$response\n\n";

echo "✅ Sysadmin Agent test completed!\n\n";

/**
 * The three local tool boundaries pass deterministic offline tests. Provider
 * selection and model behavior still require a separately reviewed live run.
 * Do not schedule or expose this example operationally without authentication,
 * redaction, retention, concurrency, timeout, and approval policy.
 */
?>
