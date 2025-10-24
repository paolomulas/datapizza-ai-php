<?php
/**
 * 🍕 Example 4: Conversation Memory - Teaching AI to Remember
 * 
 * Until now, every conversation was isolated. The agent forgot everything
 * after each run. Like talking to someone with amnesia.
 * 
 * Not anymore.
 * 
 * This example shows how to give your agent PERSISTENT MEMORY:
 * - It remembers what you told it earlier
 * - It can reference past conversations
 * - It maintains context across multiple interactions
 * 
 * How does it work?
 * - Every message (yours + agent's) gets saved to a file
 * - Before answering, the agent loads the conversation history
 * - The LLM sees the full context and responds accordingly
 * 
 * This is how ChatGPT remembers your conversation. Same principle.
 * File-based, simple, transparent. No database needed. 💾
 */

require_once __DIR__ . '/../../datapizza/agents/react_agent.php';
require_once __DIR__ . '/../../datapizza/memory/conversation_memory.php';
require_once __DIR__ . '/../../datapizza/tools/calculator.php';

// Helper functions for memory-aware agent execution (PHP 7.4 compatible)
function agent_run_with_memory($agent, $session_id, $query, $system_prompt = 'You are an educational AI tutor who remembers conversations') {
    // Load context from memory
    $context = memory_get_context($session_id, $system_prompt);
    
    // Build prompt with context
    $prompt_with_context = build_context_prompt($context, $query);
    
    // Run agent
    $response = $agent->run($prompt_with_context);
    
    // Save to memory
    memory_add($session_id, 'user', $query);
    memory_add($session_id, 'assistant', $response);
    
    return $response;
}

function build_context_prompt($context, $current_query) {
    if (empty($context)) {
        return $current_query;
    }
    
    $prompt = "Previous conversation:\n";
    foreach ($context as $msg) {
        $role = strtoupper($msg['role']);
        $content = $msg['content'];
        $prompt .= "$role: $content\n";
    }
    
    $prompt .= "\nNEW QUESTION: $current_query\n";
    $prompt .= "Answer taking into account the previous conversation.";
    
    return $prompt;
}

// Setup environment variables
$env = parse_ini_file(__DIR__ . '/../../.env');
foreach ($env as $key => $value) putenv("$key=$value");

echo "=== ReAct Agent + Conversation Memory Test ===\n\n";

// Create unique session ID for this conversation
$session_id = 'demo_session_' . date('His');
echo "Session ID: $session_id\n\n";

// Setup agent with calculator tool
// PHP 7.4: Use positional arguments
$agent = new ReactAgent(
    'openai',                    // llm_provider
    'gpt-4o-mini',              // model
    [new Calculator()],          // tools
    3,                          // max_iterations
    false                       // verbose
);

// Test 1: First interaction - Agent learns about user
echo "Test 1: First query (agent learns your name)\n";
echo str_repeat("-", 60) . "\n";

$query1 = "Hi! My name is Paolo and I'm an AI student";
echo "User: $query1\n";

// First query - using helper function
$response1 = agent_run_with_memory($agent, $session_id, $query1);
echo "Agent: $response1\n";

echo "\n⏳ Pause 2 seconds...\n\n";
sleep(2);

// Test 2: Second interaction - Agent should remember
echo "Test 2: Second query (agent should remember name)\n";
echo str_repeat("-", 60) . "\n";

$query2 = "What's my name? And what do I study?";
echo "User: $query2\n";

// Second query - WITH memory context
$response2 = agent_run_with_memory($agent, $session_id, $query2);
echo "Agent: $response2\n";

echo "\n⏳ Pause 2 seconds...\n\n";
sleep(2);

// Test 3: Tool usage + memory combination
echo "Test 3: Query with calculation (uses tool + memory)\n";
echo str_repeat("-", 60) . "\n";

$query3 = "Calculate the square root of 144 and tell me if you still remember my name";
echo "User: $query3\n";

$response3 = agent_run_with_memory($agent, $session_id, $query3);
echo "Agent: $response3\n";

// Final statistics
echo "\n" . str_repeat("=", 60) . "\n";
echo "Conversation Statistics:\n";
$stats = memory_get_stats($session_id);
echo "- Messages saved: {$stats['total_messages']}\n";
echo "- File size: {$stats['file_size_kb']} KB\n";
echo "- Path: {$stats['file_path']}\n";

echo "\n✅ Test completed! Agent now has persistent memory.\n";

/**
 * 🎓 What you just witnessed:
 * 
 * The agent REMEMBERED across multiple interactions.
 * 
 * This is huge because:
 * 1. LLMs are stateless - they forget everything after each call
 * 2. To maintain context, YOU must provide the conversation history
 * 3. The memory system handles this automatically
 * 
 * What happened behind the scenes:
 * - Query 1: Agent sees empty context, introduces itself
 * - System saves both messages to memory/demo_session_HHMMSS.json
 * - Query 2: memory_get_context() loads the history
 * - Agent sees the previous conversation in the prompt
 * - It recalls "Paolo" and "AI student" from earlier
 * - Query 3: Same process + it uses the Calculator tool too
 * 
 * This is how all conversational AI works:
 * Memory = Past messages + Current prompt → LLM → Response
 * 
 * And it's all transparent. Go check the .json file yourself!
 * You can see exactly what the agent remembers. No black boxes.
 * 
 * This is the foundation for building chatbots, tutors, assistants...
 * All running on your Raspberry Pi. 🧠
 */
?>
