<?php

/**
 * 🍕 Datapizza-AI PHP - n8n Webhook Endpoint
 * 
 * RESTful JSON API endpoint for triggering AI agents from n8n workflows.
 * Enables complex workflow automation connected to your Raspberry Pi agent.
 * 
 * Educational concepts:
 * - REST API design (POST for agent execution)
 * - CORS handling (allows n8n.cloud cross-origin requests)
 * - Request validation (ensures data integrity)
 * - Tool injection (dynamic tool configuration per request)
 * - Session management (optional conversation memory)
 * 
 * Use cases:
 * - Automated report generation triggered by schedule
 * - Customer support workflows with AI assistance
 * - Data processing pipelines with AI analysis
 * - Multi-step workflows combining AI with external services
 * 
 * Learn more: https://docs.n8n.io/integrations/webhooks/
 */

// CORS headers - Allow n8n.cloud to call this endpoint
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    n8n_error('Method not allowed. Use POST.', 405);
}

// Load dependencies
require_once __DIR__ . '/../../agents/react_agent.php';
require_once __DIR__ . '/../../agents/agent_with_memory.php';
// ... (tool includes)

// ============================================
// Environment & Configuration
// ============================================

/**
 * Loads environment variables from .env file
 * 
 * Educational note:
 * Environment variables keep secrets out of code.
 * Critical for API keys, database passwords, etc.
 */
function n8n_load_env($path = __DIR__ . '/../../.env') {
    // ... implementation
}

// ============================================
// Request Handling
// ============================================

/**
 * Parses incoming JSON request
 */
function n8n_get_request() {
    $raw = file_get_contents('php://input');
    return $raw ? json_decode($raw, true) : null;
}

/**
 * Validates request data
 * 
 * Educational concept:
 * Always validate user input! Never trust external data.
 */
function n8n_validate($data) {
    if (!$data) return 'Invalid JSON';
    if (empty($data['query'])) return 'Query required';
    return null;  // Validation passed
}

/**
 * Sends JSON response
 */
function n8n_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

/**
 * Sends error response
 */
function n8n_error($msg, $code = 400) {
    n8n_response([
        'success' => false,
        'error' => $msg,
        'timestamp' => time()
    ], $code);
}

// ============================================
// Tool Builder
// ============================================

/**
 * Builds tool array from requested tool names
 * 
 * Educational concept:
 * Dynamic tool injection - the client specifies which
 * tools the agent should have access to for this request.
 */
function n8n_build_tools($requested) {
    $tools = [];
    foreach ($requested as $name) {
        if ($name === 'calculator') $tools[] = new Calculator();
        elseif ($name === 'datetime') $tools[] = new DateTimeTool();
        elseif ($name === 'wikipedia_search') $tools[] = new WikipediaSearchTool();
        elseif ($name === 'duckduckgo_search') $tools[] = new DuckDuckGoSearchTool();
    }
    return $tools;
}

// ============================================
// Agent Execution
// ============================================

/**
 * Executes ReactAgent with optional memory
 * 
 * Educational flow:
 * 1. Build tools from request
 * 2. Create ReactAgent with selected LLM provider
 * 3. Optionally wrap with memory for conversations
 * 4. Run agent on query
 * 5. Return result
 */
function n8n_run_agent($data) {
    $query = $data['query'];
    $tool_names = $data['tools'] ?? [];
    $llm_provider = $data['llm_provider'] ?? 'deepseek';
    $session_id = $data['session_id'] ?? null;
    
    // Build tools
    $tools = n8n_build_tools($tool_names);
    
    // Create agent
    $agent = new ReactAgent($llm_provider, 'model-name', $tools);
    
    // Run with or without memory
    if ($session_id) {
        $response = agent_with_memory_run($agent, $session_id, $query);
    } else {
        $response = $agent->run($query);
    }
    
    return $response;
}

// ============================================
// Main Execution
// ============================================

n8n_load_env();

// Get and validate request
$data = n8n_get_request();
$error = n8n_validate($data);

if ($error) {
    n8n_error($error);
}

try {
    // Execute agent
    $response = n8n_run_agent($data);
    
    // Send success response
    n8n_response([
        'success' => true,
        'response' => $response,
        'session_id' => $data['session_id'] ?? null,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    n8n_error('Agent execution failed: ' . $e->getMessage(), 500);
}
