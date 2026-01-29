<?php
/**
 * 🍕 Example: Sysadmin Agent - AI-Powered Server Monitoring
 * 
 * This demonstrates how Datapizza-AI-PHP can automate system administration tasks.
 * The agent can check disk space, uptime, and search logs using natural language.
 * 
 * Perfect for:
 * - Automated health checks
 * - Log analysis without grep-fu
 * - Quick status reports for managers
 * - Learning how AI agents interact with system resources
 * 
 * Created for ADMIN Magazine article on Datapizza-AI-PHP.
 * 
 * Security note:
 * All tools use safe, read-only PHP functions or whitelisted log paths.
 * No arbitrary shell commands - safe for production-like environments.
 */

require_once __DIR__ . '/../../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../../datapizza/tools/disk_space.php';
require_once __DIR__ . '/../../datapizza/tools/system_uptime.php';
require_once __DIR__ . '/../../datapizza/tools/log_grep.php';

// Load environment variables
$env = parse_ini_file(__DIR__ . '/../../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "=== 🛠️  Sysadmin Agent Test ===\n";
echo "Demonstrating AI-powered system administration on a Raspberry Pi\n\n";

// Step 1: Initialize sysadmin tools
// These give the agent "superpowers" to interact with the system
$tools = [
    new DiskSpaceTool(),      // Check disk space
    new SystemUptimeTool(),   // Check uptime and load
    new LogGrepTool()         // Search log files safely
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

// Test 3: Log analysis (if you have syslog)
echo "\n📋 Test 3: Log Analysis\n";
echo str_repeat("=", 60) . "\n";
$response = $agent->run("Search for any errors in /var/log/syslog in the last 10 entries.");
echo "\n🎯 Final answer:\n$response\n\n";

// Test 4: Multi-step reasoning
echo "\n🧠 Test 4: Complex Query\n";
echo str_repeat("=", 60) . "\n";
$response = $agent->run("Give me a server health report: check uptime, disk space, and tell me if there are any authentication failures in the logs.");
echo "\n🎯 Final answer:\n$response\n\n";

echo "✅ Sysadmin Agent test completed!\n\n";

/**
 * 🎓 What you just witnessed:
 * 
 * The agent didn't just run commands - it UNDERSTOOD the requests.
 * 
 * For each question, the AI:
 * 1. Analyzed what you asked (natural language understanding)
 * 2. Decided which system tools to call (disk_space, uptime, log_grep)
 * 3. Executed the tools with proper parameters
 * 4. Interpreted the results in context
 * 5. Provided a human-readable summary
 * 
 * This is autonomous system monitoring.
 * The agent is making operational decisions based on system state.
 * 
 * And it's running on a 2011 Raspberry Pi with 256MB RAM,
 * using only PHP 7.x and curl for API calls.
 * 
 * No Python. No Docker. No GPU. Just HTTP and logic.
 * 
 * This proves that sophisticated AI orchestration doesn't require
 * expensive infrastructure - just good architecture. 🚀
 * 
 * ---
 * 
 * Security considerations:
 * - disk_space: Uses native PHP functions (safe, read-only)
 * - system_uptime: Reads /proc files (safe, standard Linux)
 * - log_grep: Whitelisted paths only (prevents arbitrary file access)
 * 
 * This is production-safe for monitoring tasks.
 * You can run this from cron without worrying about shell injection.
 * 
 * Want to see the agent's internal reasoning?
 * Set verbose=true in the ReactAgent constructor (already enabled above).
 */
?>
