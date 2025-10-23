<?php

/**
 * 🍕 Datapizza-AI PHP - Context Tracing
 * 
 * Traces AI execution flow for debugging and understanding.
 * Essential for seeing "what the AI is thinking" step-by-step!
 * 
 * Educational concept - Why tracing matters:
 * 
 * AI systems are BLACK BOXES. Without tracing, you can't see:
 * - Which tools the agent called and why
 * - What context was retrieved from vectorstore
 * - How prompts were constructed
 * - Where errors occurred
 * - Performance bottlenecks (slow API calls, etc.)
 * 
 * Tracing makes the invisible VISIBLE!
 * 
 * Example traced execution:
 * 
 * [14:30:01] AGENT_START: User query "What is RAG?"
 * [14:30:02] RAG_SEARCH: Searching vectorstore... (took 45ms)
 * [14:30:02] RAG_RETRIEVED: 3 documents (scores: 0.92, 0.87, 0.81)
 * [14:30:03] PROMPT_BUILD: Context: 1200 tokens
 * [14:30:03] LLM_CALL: OpenAI GPT-4 (model: gpt-4-turbo)
 * [14:30:06] LLM_RESPONSE: 450 tokens (took 3.2s, $0.015)
 * [14:30:06] AGENT_END: Total time 5.1s
 * 
 * With tracing, you can debug, optimize, and understand!
 */

// Global tracing state
$GLOBALS['trace_enabled'] = false;
$GLOBALS['trace_buffer'] = array();
$GLOBALS['trace_start_time'] = null;

/**
 * Enables tracing
 * 
 * Call at the start of your script to enable trace logging.
 * All trace_*() calls will be recorded.
 * 
 * Example:
 * trace_enable();
 * // Your AI code here...
 * $trace = trace_get_all();
 * print_r($trace);
 * 
 * @param bool $reset If true, clears existing trace buffer
 */
function trace_enable($reset = true) {
    $GLOBALS['trace_enabled'] = true;
    $GLOBALS['trace_start_time'] = microtime(true);
    
    if ($reset) {
        $GLOBALS['trace_buffer'] = array();
    }
}

/**
 * Disables tracing
 * 
 * Stops recording trace events.
 * Buffer is preserved (use trace_get_all() to retrieve).
 */
function trace_disable() {
    $GLOBALS['trace_enabled'] = false;
}

/**
 * Checks if tracing is enabled
 * 
 * @return bool True if tracing is active
 */
function trace_is_enabled() {
    return isset($GLOBALS['trace_enabled']) && $GLOBALS['trace_enabled'];
}

/**
 * Logs a trace event
 * 
 * Educational concept - Trace event structure:
 * 
 * Each event contains:
 * - timestamp: When it happened (seconds since trace start)
 * - type: Event type (AGENT_START, LLM_CALL, RAG_SEARCH, etc.)
 * - message: Human-readable description
 * - data: Additional structured data (optional)
 * 
 * Event types help you filter logs:
 * - Show only LLM_CALL events to analyze API usage
 * - Show only RAG_* events to debug retrieval
 * - Show only ERROR events to find failures
 * 
 * @param string $type Event type (e.g., 'LLM_CALL', 'RAG_SEARCH')
 * @param string $message Human-readable message
 * @param array $data Additional structured data
 */
function trace_event($type, $message, $data = array()) {
    if (!trace_is_enabled()) {
        return;  // Tracing disabled, do nothing
    }
    
    // Calculate elapsed time since trace start
    $elapsed = 0;
    if (isset($GLOBALS['trace_start_time'])) {
        $elapsed = microtime(true) - $GLOBALS['trace_start_time'];
    }
    
    // Build event structure
    $event = array(
        'timestamp' => date('H:i:s'),
        'elapsed_ms' => round($elapsed * 1000, 2),
        'type' => $type,
        'message' => $message,
        'data' => $data
    );
    
    // Add to buffer
    $GLOBALS['trace_buffer'][] = $event;
}

/**
 * Gets all trace events
 * 
 * Returns complete trace buffer for analysis.
 * 
 * @return array Array of trace events
 */
function trace_get_all() {
    return isset($GLOBALS['trace_buffer']) ? $GLOBALS['trace_buffer'] : array();
}

/**
 * Gets trace events filtered by type
 * 
 * Educational use case - Focused debugging:
 * 
 * You suspect RAG retrieval is slow. Filter by type:
 * $rag_events = trace_get_by_type('RAG_SEARCH');
 * 
 * Now you see only RAG operations and their timings!
 * 
 * @param string $type Event type to filter
 * @return array Filtered trace events
 */
function trace_get_by_type($type) {
    $all = trace_get_all();
    $filtered = array();
    
    foreach ($all as $event) {
        if ($event['type'] === $type) {
            $filtered[] = $event;
        }
    }
    
    return $filtered;
}

/**
 * Prints trace to console/stdout (human-readable)
 * 
 * Educational format - Timeline view:
 * 
 * [00:00.00] AGENT_START: User query received
 * [00:00.12] RAG_SEARCH: Searching vectorstore (query: "What is AI?")
 * [00:00.15] RAG_RETRIEVED: Found 3 documents
 * [00:00.20] LLM_CALL: Calling OpenAI GPT-4
 * [00:03.45] LLM_RESPONSE: Received response (450 tokens)
 * [00:03.50] AGENT_END: Total execution time
 * 
 * Easy to read, shows timing, shows flow!
 * 
 * @param bool $include_data If true, prints data payload too
 */
function trace_print($include_data = false) {
    $events = trace_get_all();
    
    if (empty($events)) {
        echo "No trace events recorded.\n";
        return;
    }
    
    echo "=== TRACE LOG (" . count($events) . " events) ===\n\n";
    
    foreach ($events as $event) {
        // Format elapsed time as MM:SS.mmm
        $seconds = floor($event['elapsed_ms'] / 1000);
        $ms = $event['elapsed_ms'] % 1000;
        $elapsed = sprintf('%02d:%06.3f', floor($seconds / 60), ($seconds % 60) + ($ms / 1000));
        
        // Print main event line
        printf("[%s] %s: %s\n", $elapsed, $event['type'], $event['message']);
        
        // Optionally print data payload
        if ($include_data && !empty($event['data'])) {
            echo "    Data: " . json_encode($event['data']) . "\n";
        }
    }
    
    echo "\n";
}

/**
 * Exports trace to JSON file
 * 
 * Educational use case - Persistent debugging:
 * 
 * Save traces to analyze later or compare runs:
 * trace_export('debug_run_001.json');
 * 
 * Load in Python/JavaScript for visualization:
 * - Plot timeline graphs
 * - Analyze performance trends
 * - Share with team for debugging
 * 
 * @param string $filepath Path to save JSON file
 * @return bool True on success
 */
function trace_export($filepath) {
    $events = trace_get_all();
    $json = json_encode($events, JSON_PRETTY_PRINT);
    
    return file_put_contents($filepath, $json) !== false;
}

/**
 * Gets trace statistics (summary metrics)
 * 
 * Educational concept - Performance analysis:
 * 
 * Instead of reading 100 trace events, get summary:
 * - Total execution time
 * - Number of LLM calls
 * - Average LLM response time
 * - Number of RAG searches
 * - Most common event types
 * 
 * Quick overview of execution profile!
 * 
 * @return array Statistics array
 */
function trace_stats() {
    $events = trace_get_all();
    
    if (empty($events)) {
        return array();
    }
    
    $stats = array(
        'total_events' => count($events),
        'total_time_ms' => end($events)['elapsed_ms'],
        'event_types' => array(),
        'llm_calls' => 0,
        'rag_searches' => 0,
        'errors' => 0
    );
    
    // Count event types
    foreach ($events as $event) {
        $type = $event['type'];
        
        if (!isset($stats['event_types'][$type])) {
            $stats['event_types'][$type] = 0;
        }
        $stats['event_types'][$type]++;
        
        // Count specific interesting events
        if (strpos($type, 'LLM') !== false) {
            $stats['llm_calls']++;
        }
        if (strpos($type, 'RAG') !== false) {
            $stats['rag_searches']++;
        }
        if (strpos($type, 'ERROR') !== false) {
            $stats['errors']++;
        }
    }
    
    return $stats;
}

/**
 * Helper: Traces agent iteration (ReAct loop)
 * 
 * Convenience function for tracing agent thinking steps.
 * 
 * @param int $iteration Iteration number
 * @param string $thought Agent's reasoning
 * @param string $action Action to take
 * @param string $observation Result of action
 */
function trace_agent_iteration($iteration, $thought, $action, $observation = '') {
    trace_event('AGENT_THINK', "Iteration $iteration: $thought");
    trace_event('AGENT_ACTION', "Action: $action");
    
    if (!empty($observation)) {
        trace_event('AGENT_OBSERVE', "Observation: " . substr($observation, 0, 100));
    }
}

/**
 * Helper: Traces LLM call with timing
 * 
 * Wrap LLM API calls with this for automatic tracing.
 * 
 * Example:
 * $start = microtime(true);
 * $response = llm_call($prompt);
 * trace_llm_call('gpt-4', strlen($prompt), strlen($response), microtime(true) - $start);
 * 
 * @param string $model Model name
 * @param int $prompt_tokens Approximate input tokens
 * @param int $response_tokens Approximate output tokens
 * @param float $duration_sec Call duration in seconds
 */
function trace_llm_call($model, $prompt_tokens, $response_tokens, $duration_sec) {
    $data = array(
        'model' => $model,
        'prompt_tokens' => $prompt_tokens,
        'response_tokens' => $response_tokens,
        'duration_ms' => round($duration_sec * 1000, 2)
    );
    
    $message = sprintf(
        "LLM call: %s (%d→%d tokens, %.2fs)",
        $model,
        $prompt_tokens,
        $response_tokens,
        $duration_sec
    );
    
    trace_event('LLM_CALL', $message, $data);
}

/**
 * Helper: Traces RAG search with results
 * 
 * @param string $query Search query
 * @param int $num_results Number of results found
 * @param array $scores Similarity scores
 */
function trace_rag_search($query, $num_results, $scores = array()) {
    $data = array(
        'query' => $query,
        'num_results' => $num_results,
        'scores' => $scores
    );
    
    $scores_str = empty($scores) ? '' : ' (scores: ' . implode(', ', array_map(function($s) {
        return round($s, 2);
    }, $scores)) . ')';
    
    $message = "RAG search: \"$query\" → $num_results results$scores_str";
    
    trace_event('RAG_SEARCH', $message, $data);
}
