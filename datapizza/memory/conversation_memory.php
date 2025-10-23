<?php

/**
 * 🍕 Datapizza-AI PHP - Conversation Memory
 * 
 * Simple file-based conversation memory for multi-turn AI interactions.
 * Stores conversation history per session to maintain context across
 * multiple user queries.
 * 
 * Educational concepts:
 * - Session-based conversation tracking (like web sessions)
 * - Context window management (limits for LLM memory)
 * - File-based persistence (no database needed)
 * - OpenAI-compatible message format (portability)
 * 
 * Why conversation memory matters:
 * Without memory, each query is isolated - the AI can't remember
 * what you talked about 2 messages ago. Memory enables:
 * - Follow-up questions ("tell me more about that")
 * - Context-aware responses ("as I mentioned earlier...")
 * - Multi-turn problem solving (step-by-step guidance)
 * 
 * Architecture choice - File-based storage:
 * Perfect for Raspberry Pi because:
 * - No database overhead (no MySQL/PostgreSQL needed)
 * - Simple debugging (just open the JSON file)
 * - Low memory footprint
 * - Easy backup (copy the files)
 */

/**
 * Initializes or loads conversation memory for a session
 * 
 * This function handles the session lifecycle:
 * 1. Creates data directory if it doesn't exist
 * 2. Loads existing conversation from JSON file
 * 3. Returns empty array for new sessions
 * 
 * Educational concept - Session ID:
 * Think of session_id like a room number. Each user/conversation
 * gets its own room where all their messages are stored.
 * Different session_ids = different conversations, completely isolated.
 * 
 * @param string $session_id Unique identifier for this conversation
 * @return array Array of message objects [{role, content, timestamp}, ...]
 */
function memory_init($session_id = 'default') {
    // Ensure storage directory exists
    $dir = __DIR__ . '/../../data/conversations/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Load existing conversation from JSON file
    $file = $dir . $session_id . '.json';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    
    // New session - return empty conversation
    return [];
}

/**
 * Adds a message to conversation memory
 * 
 * This appends a new message to the conversation history and
 * automatically manages the context window by keeping only
 * the most recent N messages.
 * 
 * Educational concept - Context Window:
 * LLMs have limited "memory" - they can only process so many tokens.
 * If we send 1000 messages, the LLM API will reject it or charge $$$$.
 * Solution: Keep only recent messages (sliding window).
 * 
 * On Raspberry Pi, this also helps with RAM - we don't want to load
 * massive conversation files into PHP memory.
 * 
 * @param string $session_id Session to add message to
 * @param string $role Message role ('user' or 'assistant')
 * @param string $content Message text content
 * @param int $max_messages Maximum messages to keep (default: 20)
 */
function memory_add($session_id, $role, $content, $max_messages = 20) {
    // Load current conversation
    $messages = memory_init($session_id);
    
    // Append new message with timestamp
    // Timestamp is useful for debugging and analytics
    $messages[] = [
        'role' => $role,        // 'user' or 'assistant'
        'content' => $content,  // The actual message text
        'timestamp' => time()   // Unix timestamp (seconds since 1970)
    ];
    
    // Keep only last N messages (context window management)
    // This prevents unbounded growth and keeps RAM usage low
    if (count($messages) > $max_messages) {
        $messages = array_slice($messages, -$max_messages);
    }
    
    // Save to file as pretty-printed JSON
    // JSON_UNESCAPED_UNICODE: Preserves emoji and international characters
    // JSON_PRETTY_PRINT: Makes file human-readable for debugging
    $file = __DIR__ . '/../../data/conversations/' . $session_id . '.json';
    file_put_contents(
        $file, 
        json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

/**
 * Retrieves conversation history with optional system prompt
 * 
 * This formats the conversation for sending to an LLM.
 * The returned array follows OpenAI's Chat Completions format,
 * making it compatible with OpenAI, Anthropic, DeepSeek, etc.
 * 
 * Educational concept - System Prompt:
 * The system prompt sets the AI's behavior and personality.
 * It's like giving instructions before the conversation starts:
 * - "You are a helpful assistant"
 * - "You are a Python expert"
 * - "You always respond in haiku format"
 * 
 * System prompt goes first, then all conversation messages follow.
 * 
 * @param string $session_id Session to retrieve
 * @param string|null $system_prompt Optional system prompt to prepend
 * @return array Array of messages in OpenAI format
 */
function memory_get_context($session_id, $system_prompt = null) {
    // Load conversation messages
    $messages = memory_init($session_id);
    
    $context = [];
    
    // Add system prompt if provided
    // System message always comes first
    if ($system_prompt) {
        $context[] = [
            'role' => 'system',
            'content' => $system_prompt
        ];
    }
    
    // Add all conversation messages
    // Format: [{role: 'user', content: '...'}, {role: 'assistant', content: '...'}, ...]
    foreach ($messages as $msg) {
        $context[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    return $context;
}

/**
 * Clears conversation memory for a session
 * 
 * Deletes the conversation file, effectively "forgetting" everything.
 * Useful for:
 * - Starting fresh conversations
 * - Privacy (remove user data)
 * - Testing (reset to clean state)
 * 
 * Educational note:
 * In production, you might want to archive instead of delete,
 * or implement a "soft delete" for compliance/auditing.
 * 
 * @param string $session_id Session to clear
 */
function memory_clear($session_id) {
    $file = __DIR__ . '/../../data/conversations/' . $session_id . '.json';
    if (file_exists($file)) {
        unlink($file);
    }
}

/**
 * Gets statistics about a conversation session
 * 
 * Returns metadata useful for monitoring and debugging:
 * - How many messages in this conversation?
 * - How big is the file (approaching limits?)
 * - Does this session exist?
 * 
 * Educational use case:
 * If your Raspberry Pi is running slow, check if conversation
 * files are getting too large. Maybe increase context window
 * truncation or archive old conversations.
 * 
 * @param string $session_id Session to analyze
 * @return array Statistics: [message_count, file_size_kb, exists]
 */
function memory_get_stats($session_id) {
    $file = __DIR__ . '/../../data/conversations/' . $session_id . '.json';
    
    if (!file_exists($file)) {
        return [
            'exists' => false,
            'message_count' => 0,
            'file_size_kb' => 0
        ];
    }
    
    // Load and count messages
    $messages = memory_init($session_id);
    
    // Get file size in kilobytes
    $file_size = filesize($file);
    $file_size_kb = round($file_size / 1024, 2);
    
    return [
        'exists' => true,
        'message_count' => count($messages),
        'file_size_kb' => $file_size_kb
    ];
}
