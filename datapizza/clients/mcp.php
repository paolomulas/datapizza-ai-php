<?php

/**
 * 🍕 Datapizza-AI PHP - MCP (Model Context Protocol) Client
 * 
 * Client for Anthropic's Model Context Protocol (MCP).
 * MCP is a standard way for AI models to access external tools,
 * databases, and services through a unified interface.
 * 
 * Educational concept:
 * MCP solves the "tool calling" problem by providing a standard
 * protocol (JSON-RPC 2.0) that any AI model can use to interact
 * with external systems. Think of it as "USB for AI tools".
 * 
 * Why MCP matters:
 * - Standardizes tool integration across AI providers
 * - Uses JSON-RPC 2.0 (well-established protocol)
 * - Enables composable AI architectures
 * 
 * Learn more: https://modelcontextprotocol.io
 */

require_once __DIR__ . '/dpz_call.php';

/**
 * Calls a tool through an MCP server
 * 
 * MCP servers expose tools via JSON-RPC 2.0 endpoints.
 * This function acts as a client, sending tool call requests
 * and receiving results.
 * 
 * Educational note:
 * On Raspberry Pi, you should use cloud-hosted MCP servers
 * (Render, Railway, etc.) since localhost isn't accessible
 * from external AI services.
 * 
 * @param string $tool_name Name of the tool to call
 * @param array $arguments Arguments to pass to the tool
 * @return array Tool execution result or error
 */
function mcp_call_tool($tool_name, $arguments = []) {
    
    // Get MCP server URL from environment
    // Should be a cloud endpoint, not localhost
    $server_url = getenv('MCP_SERVER_URL');
    
    if (!$server_url) {
        return ['error' => 'MCP_SERVER_URL not configured in .env'];
    }
    
    // Validate that it's a cloud endpoint
    // Localhost won't work for external AI services connecting to your RPi
    if (strpos($server_url, 'localhost') !== false) {
        return ['error' => 'Use cloud MCP server (Render/Railway), not localhost'];
    }
    
    // Build JSON-RPC 2.0 payload
    // This is the standard format for MCP communication
    $payload = [
        'jsonrpc' => '2.0',           // JSON-RPC version
        'method' => 'tools/call',     // MCP method for tool execution
        'params' => [
            'name' => $tool_name,
            'arguments' => $arguments
        ],
        'id' => uniqid()              // Unique request identifier
    ];

    // Make HTTP POST request to MCP server
    $ch = curl_init($server_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);  // 15 second timeout
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Check for HTTP errors
    if ($http_code !== 200) {
        return ['error' => "MCP server failed: HTTP $http_code"];
    }
    
    // Parse JSON-RPC response
    $result = json_decode($response, true);
    
    // Return the result field, or full response if no result field
    return $result['result'] ?? $result;
}
