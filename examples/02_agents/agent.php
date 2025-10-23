<?php
/**
 * 🍕 Example 2: ReAct Agent - When AI Learns to Use Tools
 * 
 * Now things get interesting. This isn't just text generation anymore.
 * You're building an AI agent that can:
 * - REASON about what it needs to do
 * - ACT by calling tools (calculator, file reader, datetime)
 * - OBSERVE the results and decide next steps
 * 
 * This is the ReAct pattern (Reason + Act) - a real AI decision loop.
 * 
 * What makes this powerful:
 * - The LLM doesn't just answer, it *thinks* through steps
 * - It can use external tools to solve problems it couldn't solve alone
 * - Multiple iterations let it break down complex tasks
 * 
 * Watch how it reasons. That's where the magic happens. 🧠
 */

require_once __DIR__ . '/../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../datapizza/tools/calculator.php';
require_once __DIR__ . '/../datapizza/tools/datetime_tool.php';
require_once __DIR__ . '/../datapizza/tools/file_reader.php';

// Load environment variables
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "=== ReAct Agent Test ===\n\n";

// Step 1: Create available tools
// These are "superpowers" you're giving to the AI
$tools = [
    new Calculator(),      // Let it do math
    new DateTimeTool(),    // Let it work with dates
    new FileReader()       // Let it read files
];

// Step 2: Initialize the ReAct agent
// verbose=true shows you the agent's internal reasoning process
$agent = new ReActAgent(
    tools: $tools,
    llm_provider: 'openai',
    model: 'gpt-4o-mini',
    max_iterations: 5,     // How many think-act cycles allowed
    verbose: true          // 🎓 Educational mode: see the reasoning!
);

// Test 1: Simple calculation
// The agent will recognize it needs the calculator tool
echo "Test 1: Mathematical calculation\n";
echo str_repeat("=", 50) . "\n";
$response = $agent->run("What is 15.7% of 8432?");
echo "\n🎯 Final answer: $response\n\n";

// Test 2: Date operations
// The agent will use the datetime tool
echo "\nTest 2: Date calculation\n";
echo str_repeat("=", 50) . "\n";
$response = $agent->run("How many days until Christmas 2025?");
echo "\n🎯 Final answer: $response\n\n";

// Test 3: Multi-step reasoning
// This requires the agent to combine multiple tool calls and logic
echo "\nTest 3: Multi-step reasoning\n";
echo str_repeat("=", 50) . "\n";
$response = $agent->run("Calculate how many working days (excluding Saturday and Sunday) until Christmas 2025");
echo "\n🎯 Final answer: $response\n\n";

echo "✅ Test completed!\n";

/**
 * 🎓 What you just witnessed:
 * 
 * The agent didn't just execute commands. It THOUGHT.
 * 
 * For each question, it:
 * 1. Analyzed what was being asked
 * 2. Decided which tool(s) to use
 * 3. Called the tool with proper parameters
 * 4. Got the result back
 * 5. Reasoned about whether it had enough info to answer
 * 6. Either used another tool or gave the final answer
 * 
 * This is autonomous behavior. The agent is making decisions.
 * 
 * And it's all running on your 2011 Raspberry Pi, using plain PHP.
 * No TensorFlow. No PyTorch. Just HTTP calls and logic.
 * 
 * This is how modern AI agents work - and now you've built one. 🚀
 */
?>
