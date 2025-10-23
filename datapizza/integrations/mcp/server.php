<?php

/**
 * 🍕 Datapizza-AI PHP - MCP Server
 * 
 * Implementation of Anthropic's Model Context Protocol (MCP).
 * Exposes PHP tools to desktop AI applications (Claude, Cursor, Zed)
 * via JSON-RPC 2.0 over STDIO.
 * 
 * Educational concepts:
 * - JSON-RPC 2.0: Standard protocol for remote procedure calls
 * - STDIO communication: stdin for requests, stdout for responses
 * - Protocol negotiation: Capability exchange during initialization
 * - Tool discovery: AI can list available tools dynamically
 * 
 * Why this matters:
 * MCP standardizes how AI models access external tools. Before MCP,
 * every AI app had its own tool format. Now, write tools once,
 * use everywhere (Claude Desktop, Cursor, Zed, etc.)
 * 
 * Learn more: https://modelcontextprotocol.io
 */

// Load required dependencies
require_once __DIR__ . '/../../tools/calculator.php';
require_once __DIR__ . '/../../tools/datetime.php';
require_once __DIR__ . '/../../tools/wikipedia_search.php';

// ============================================
// Tool Registry
// ============================================

/**
 * Returns available tools with metadata
 * 
 * Educational note:
 * This registry pattern allows dynamic tool discovery.
 * The AI can ask "what tools are available?" and get
 * back descriptions of what each tool does.
 */
function mcp_get_tool_info() {
    return [
        'calculator' => [
            'instance' => new Calculator(),
            'description' => 'Performs mathematical calculations. Supports +, -, *, /, %, sqrt, pow, sin, cos, etc.'
        ],
        'datetime' => [
            'instance' => new DateTimeTool(),
            'description' => 'Provides current date and time information'
        ],
        'wikipedia_search' => [
            'instance' => new WikipediaSearchTool(),
            'description' => 'Searches Wikipedia for information'
        ]
    ];
}

/**
 * Finds a tool instance by name
 * 
 * @param string $name Tool name
 * @return object|null Tool instance or null if not found
 */
function mcp_find_tool($name) {
    $tools = mcp_get_tool_info();
    return $tools[$name]['instance'] ?? null;
}

// ============================================
// Protocol Handlers
// ============================================

/**
 * Handles MCP initialize request
 * 
 * This is the handshake phase where client and server
 * negotiate capabilities and exchange metadata.
 * 
 * Educational note:
 * Protocol version is important! Client and server must
 * agree on a compatible version to communicate.
 * 
 * @param array $params Initialization parameters from client
 * @return array Server capabilities and metadata
 */
function mcp_initialize($params) {
    return [
        'protocolVersion' => '2024-11-05',
        'capabilities' => [
            'tools' => ['listChanged' => true]  // We support tool listing
        ],
        'serverInfo' => [
            'name' => 'DataPizza-AI-PHP',
            'version' => '1.0.0'
        ]
    ];
}

/**
 * Handles tools/list request
 * 
 * Returns a list of all available tools with their schemas.
 * The AI uses this to understand what tools it can call.
 * 
 * Educational concept:
 * This is "tool discovery" - the AI doesn't need hardcoded
 * knowledge of tools. It can dynamically learn what's available.
 * 
 * @return array List of tool definitions
 */
function mcp_list_tools() {
    $list = [];
    $tools = mcp_get_tool_info();
    
    foreach ($tools as $name => $info) {
        $list[] = [
            'name' => $name,
            'description' => $info['description'],
            'inputSchema' => [
                'type' => 'object',
                'properties' => $info['instance']->get_parameters_schema(),
                'required' => []  // Tool-specific required fields
            ]
        ];
    }
    
    return ['tools' => $list];
}

/**
 * Handles tools/call request
 * 
 * Executes a tool with provided arguments and returns result.
 * This is where the actual tool execution happens.
 * 
 * Educational note:
 * Error handling is critical here. If a tool fails, we return
 * a proper JSON-RPC error so the AI knows what went wrong.
 * 
 * @param array $params Tool name and arguments
 * @return array Tool execution result or error
 */
function mcp_call_tool($params) {
    $tool_name = $params['name'] ?? null;
    $arguments = $params['arguments'] ?? [];
    
    // Find the requested tool
    $tool = mcp_find_tool($tool_name);
    
    if (!$tool) {
        // Return JSON-RPC error for unknown tool
        return [
            'error' => [
                'code' => -32602,  // Invalid params
                'message' => "Unknown tool: $tool_name"
            ]
        ];
    }
    
    try {
        // Execute the tool
        $result = $tool->execute($arguments);
        
        // Format result in MCP format
        return [
            'content' => [
                ['type' => 'text', 'text' => $result]
            ]
        ];
        
    } catch (Exception $e) {
        // Return JSON-RPC error for execution failure
        return [
            'error' => [
                'code' => -32603,  // Internal error
                'message' => "Tool execution failed: " . $e->getMessage()
            ]
        ];
    }
}

// ============================================
// Main Loop - STDIO Communication
// ============================================

/**
 * Main server loop
 * 
 * Educational concept - STDIO Protocol:
 * 1. Read from STDIN (client sends JSON-RPC request)
 * 2. Parse JSON and extract method
 * 3. Call appropriate handler
 * 4. Write to STDOUT (server sends JSON-RPC response)
 * 5. Repeat
 * 
 * This is synchronous request-response. Each request gets
 * exactly one response before the next request is processed.
 */

// Disable output buffering for real-time STDIO communication
ob_implicit_flush(true);

while ($line = fgets(STDIN)) {
    $line = trim($line);
    
    // Skip empty lines
    if (empty($line)) continue;
    
    // Parse JSON-RPC request
    $request = json_decode($line, true);
    
    // Validate JSON parsing
    if ($request === null) {
        // Invalid JSON - send parse error
        echo json_encode([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32700,
                'message' => 'Parse error'
            ],
            'id' => null
        ]) . "\n";
        continue;
    }
    
    // Extract request details
    $method = $request['method'] ?? null;
    $params = $request['params'] ?? [];
    $id = $request['id'] ?? null;
    
    // Route request to appropriate handler
    switch ($method) {
        case 'initialize':
            $result = mcp_initialize($params);
            break;
            
        case 'tools/list':
            $result = mcp_list_tools();
            break;
            
        case 'tools/call':
            $result = mcp_call_tool($params);
            break;
            
        default:
            // Unknown method - send method not found error
            $result = [
                'error' => [
                    'code' => -32601,
                    'message' => "Method not found: $method"
                ]
            ];
    }
    
    // Build JSON-RPC response
    $response = [
        'jsonrpc' => '2.0',
        'id' => $id
    ];
    
    // Add result or error to response
    if (isset($result['error'])) {
        $response['error'] = $result['error'];
    } else {
        $response['result'] = $result;
    }
    
    // Send response to STDOUT
    echo json_encode($response) . "\n";
}
