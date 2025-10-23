<?php
/**
 * 🍕 Example 3: ReAct Agent + External Knowledge (Wikipedia)
 * 
 * Now your AI can access the entire internet's knowledge.
 * 
 * In the previous example, the agent could calculate and work with dates.
 * But what if you ask "How many people live in Milan?"
 * The LLM alone doesn't know current facts. It needs to LOOK THEM UP.
 * 
 * That's what we're adding here:
 * - WikipediaSearchTool gives your agent access to Wikipedia's API
 * - The agent can now search for real-world facts in real-time
 * - It decides WHEN to search vs when to calculate vs when it knows enough
 * 
 * This is how ChatGPT plugins work. Same principle. Your Raspberry Pi. 🌍
 */

require_once __DIR__ . '/../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../datapizza/tools/calculator.php';
require_once __DIR__ . '/../datapizza/tools/datetime_tool.php';
require_once __DIR__ . '/../datapizza/tools/wikipedia_search.php';

// Load environment variables
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

echo "=== ReAct Agent + Wikipedia Test ===\n";
echo "Running on: Raspberry Pi Model B (2011)\n\n";

// Step 1: Create available tools
// Now with Wikipedia access!
$tools = [
    new Calculator(),              // Math operations
    new DateTimeTool(),            // Date/time calculations
    new WikipediaSearchTool()      // 🌍 Real-world knowledge lookup
];

// Step 2: Initialize the agent
// Same setup as before - we just added a new tool to the toolkit
$agent = new ReactAgent(
    tools: $tools,
    llm_provider: 'openai',
    model: 'gpt-4o-mini',
    max_iterations: 5,
    verbose: true                  // Watch it decide when to search Wikipedia!
);

// Test 1: Wikipedia search
// The agent will recognize it needs external knowledge
echo "Test 1: Wikipedia search\n";
echo str_repeat("=", 70) . "\n";
$response = $agent->run("How many people live in Milan?");
echo "\n🎯 Final answer: $response\n\n";

// Test 2: Calculation
// The agent will use the calculator (no Wikipedia needed)
echo "\nTest 2: Mathematical calculation\n";
echo str_repeat("=", 70) . "\n";
$response = $agent->run("Calculate 15% of 350");
echo "\n🎯 Final answer: $response\n\n";

echo "✅ Test completed!\n";

/**
 * 🎓 What's powerful about this:
 * 
 * The agent now has ACCESS TO EXTERNAL KNOWLEDGE.
 * 
 * It doesn't need to have all facts memorized in the LLM.
 * When it needs current information, it:
 * 1. Recognizes "I don't know this, I need to search"
 * 2. Calls Wikipedia API with a search query
 * 3. Reads the summary returned
 * 4. Extracts the answer
 * 5. Responds to you
 * 
 * This is the foundation of RAG (Retrieval-Augmented Generation):
 * - Retrieve information when needed
 * - Augment the LLM's knowledge
 * - Generate accurate answers
 * 
 * You just built a knowledge-connected AI agent.
 * On a 12-year-old Raspberry Pi. With plain PHP.
 * 
 * This is the bridge to the next section: full RAG pipelines. 🚀
 */
?>
