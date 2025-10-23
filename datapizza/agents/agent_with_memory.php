<?php

/**
 * 🍕 Datapizza-AI PHP - Agent with Memory
 * 
 * This extends any agent to add conversational memory.
 * Instead of treating each query as isolated, it remembers
 * the conversation history and provides context.
 * 
 * Educational concepts:
 * - Composition over inheritance (wrapping existing agents)
 * - Session-based memory management
 * - Context window management for AI models
 * - Persistent storage patterns for conversations
 */

require_once '../memory/memory.php';  // Memory management functions

/**
 * Wraps any agent to add conversational memory capabilities
 * 
 * This demonstrates the "decorator pattern" - we wrap an existing
 * agent and add memory functionality without changing its core behavior.
 * 
 * @param object $base_agent Any agent that implements run() method
 * @param string $session_id Unique identifier for this conversation
 * @param string $query User's current question
 * @return string Agent's response with conversation context
 */
function agent_with_memory_run($base_agent, $session_id, $query) {
    
    // Step 1: Retrieve conversation history for this session
    // This gives us context about what the user and agent discussed before
    $context = memory_get($session_id);
    
    // Step 2: Build enriched prompt with conversation history
    // Instead of just asking the current question, we provide context
    $prompt_with_context = build_context_prompt($context, $query);
    
    // Step 3: Run the wrapped agent with the enriched prompt  
    // The base agent doesn't know about memory - it just processes the prompt
    $response = $base_agent->run($prompt_with_context);
    
    // Step 4: Save both user query and agent response to memory
    // This builds up the conversation history for future queries
    memory_add($session_id, 'user', $query);
    memory_add($session_id, 'assistant', $response);
    
    return $response;
}

/**
 * Builds a context-aware prompt from conversation history
 * 
 * This takes the raw conversation history and formats it into
 * a prompt that helps the AI understand the ongoing conversation.
 * 
 * Educational note: This is where we handle "context window management" -
 * making sure the conversation history fits in the AI model's limits.
 * 
 * @param array $context Previous messages in this conversation
 * @param string $current_query User's current question
 * @return string Formatted prompt with conversation context
 */
function build_context_prompt($context, $current_query) {
    // If no previous conversation, just return the current query
    if (empty($context)) {
        return $current_query;
    }

    // Build formatted conversation history
    $prompt = "Previous conversation:\n";
    foreach ($context as $msg) {
        $role = strtoupper($msg['role']);  // USER: or ASSISTANT:
        $content = $msg['content'];
        $prompt .= "$role: $content\n";
    }

    // Add current query with clear separation
    $prompt .= "\nNEW QUESTION: $current_query\n";
    $prompt .= "Please respond taking into account the previous conversation.";
    
    return $prompt;
}
