<?php

/**
 * 🍕 Datapizza-AI PHP - Generic Webhook Trigger
 * 
 * Universal webhook client for integrating with any service
 * that accepts HTTP POST webhooks (Zapier, Make, IFTTT, etc.).
 * 
 * Educational concept:
 * Webhooks are the glue of the modern internet. They allow
 * services to communicate without polling or complex protocols.
 * Just HTTP POST with JSON - simple and universal.
 * 
 * Use cases for AI agents:
 * - Notify external systems when agent completes a task
 * - Send agent logs to monitoring services
 * - Trigger downstream automation chains
 * - Integrate with services that don't have PHP SDKs
 */

/**
 * Sends a POST request to any webhook URL
 * 
 * This is a generic function that can trigger any webhook-based
 * integration. It's the foundation for connecting your AI agent
 * to the broader internet of services.
 * 
 * @param string $url Webhook URL to POST to
 * @param array $data Data to send (will be JSON-encoded)
 * @return array Response with success status and parsed response
 */
function webhook_trigger($url, $data) {
    
    // Make HTTP POST request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Return structured response
    // Success is any 2xx status code
    return [
        'success' => ($http_code >= 200 && $http_code < 300),
        'http_code' => $http_code,
        'response' => json_decode($response, true)
    ];
}

/**
 * Convenience function for notifying about agent queries
 * 
 * This wraps webhook_trigger() with a specific format for
 * sending agent activity to external monitoring systems.
 * 
 * @param string $url Webhook URL to notify
 * @param string $query User's original query
 * @param string $response Agent's response
 * @param array $tools Tools used by the agent
 * @return array Response from webhook_trigger()
 */
function webhook_agent_notify($url, $query, $response, $tools = []) {
    return webhook_trigger($url, [
        'action' => 'agent_query',
        'query' => $query,
        'response' => $response,
        'tools' => $tools,
        'timestamp' => date('c')  // ISO 8601 timestamp
    ]);
}
