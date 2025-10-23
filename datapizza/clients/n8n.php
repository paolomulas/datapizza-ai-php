<?php

/**
 * 🍕 Datapizza-AI PHP - n8n Workflow Integration
 * 
 * Client for triggering n8n workflows from your Raspberry Pi.
 * n8n is a powerful workflow automation tool (like Zapier but self-hostable).
 * 
 * Educational use case:
 * Your Raspberry Pi AI agent can trigger complex workflows:
 * - Send results to Slack/Discord/Email
 * - Update Google Sheets or Airtable
 * - Create tasks in project management tools
 * - Chain multiple AI services together
 * 
 * Why n8n for learning:
 * - Visual workflow builder (easy to understand)
 * - Free tier on n8n.cloud for experimentation
 * - Self-hostable for advanced users
 * - Integrates with 300+ services
 * 
 * Learn more: https://n8n.io
 */

/**
 * Triggers an n8n workflow via webhook
 * 
 * n8n workflows can be triggered by webhooks, making it easy
 * to integrate your AI agent with external services.
 * 
 * Educational note on networking:
 * When using Raspberry Pi, use n8n.cloud or a public n8n instance.
 * Localhost won't work because n8n needs to be accessible from
 * the internet to receive webhooks from external AI services.
 * 
 * @param string $query User query to send to workflow
 * @param array $tools Available tools the agent is using
 * @return array Workflow response or error
 */
function n8n_trigger($query, $tools = []) {
    
    // Get n8n webhook URL from environment
    // Format: https://your-instance.n8n.cloud/webhook/your-webhook-id
    $webhook_url = getenv('N8N_WEBHOOK_URL');
    
    if (!$webhook_url) {
        return ['error' => 'N8N_WEBHOOK_URL not configured in .env'];
    }
    
    // Validate that it's not localhost
    // This is important for Raspberry Pi deployments
    if (strpos($webhook_url, 'localhost') !== false || strpos($webhook_url, '127.0.0.1') !== false) {
        return ['error' => 'Use n8n.cloud or external service, not localhost on RPi'];
    }
    
    // Build webhook payload
    // This is the data that n8n will receive and process
    $data = [
        'action' => 'agent_query',
        'query' => $query,
        'tools' => $tools,
        'timestamp' => date('c'),                    // ISO 8601 timestamp
        'source' => 'datapizza-ai-php-rpi'           // Identify the source
    ];

    // Make HTTP POST request to n8n webhook
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: DataPizza-AI-PHP/1.2-RPi'      // Custom user agent
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);          // 30 second timeout for workflows
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Check for errors
    if ($http_code !== 200) {
        return ['error' => "n8n.cloud failed: HTTP $http_code"];
    }
    
    // Return parsed response
    return json_decode($response, true);
}
