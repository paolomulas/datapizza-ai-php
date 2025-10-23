<?php

/**
 * 🍕 Datapizza-AI PHP - Base Agent
 * 
 * This is the foundation class for all AI agents in our system.
 * Think of it as the "skeleton" - it provides the basic structure 
 * that all specific agents (ReAct, Memory, etc.) will build upon.
 * 
 * Key educational concepts:
 * - Abstract classes define interfaces without implementation
 * - Tool registration creates a unified way to extend agent capabilities
 * - Error handling makes the system robust for educational exploration
 */

abstract class BaseAgent {
    protected $llm_provider;    // Which AI service to call (OpenAI, DeepSeek, etc.)
    protected $model;          // Which model to use (gpt-4, deepseek-chat, etc.)
    protected $tools;          // Array of available tools this agent can use
    protected $max_iterations; // Safety limit to prevent infinite loops
    protected $verbose;        // Should we print debug info while learning?

    /**
     * Constructor - Sets up the basic agent infrastructure
     * 
     * @param string $llm_provider Which AI service to use ('openai', 'deepseek', etc.)
     * @param string $model Which specific model to call
     * @param array $tools Array of tool objects this agent can use
     * @param int $max_iterations Safety limit for reasoning loops
     * @param bool $verbose Enable debug output for educational purposes
     */
    public function __construct($llm_provider, $model, $tools = [], $max_iterations = 5, $verbose = false) {
        $this->llm_provider = $llm_provider;
        $this->model = $model;
        $this->max_iterations = $max_iterations;
        $this->verbose = $verbose;
        
        // Build tools registry - creates name => tool object mapping
        // This lets us call tools by name later: $this->tools['wikipedia']
        foreach ($tools as $tool) {
            $this->tools[$tool->get_name()] = $tool;
        }
    }

    /**
     * Main method: executes the agent on a user query
     * 
     * This is abstract - each agent type implements its own reasoning strategy.
     * ReAct agents loop through Thought->Action->Observation cycles.
     * Memory agents add conversational context before processing.
     * 
     * @param string $query User's question or request
     * @return string Final response from the agent
     */
    abstract public function run($query);

    /**
     * Builds human-readable description of available tools
     * 
     * This creates the text that gets sent to the AI model so it knows
     * what tools it can use and how to use them. The AI reads this and
     * learns to call tools with proper JSON parameters.
     * 
     * @return string Formatted description of all available tools
     */
    protected function build_tools_description() {
        if (empty($this->tools)) {
            return "You have no tools available.";
        }

        $description = "You have access to the following tools:\n\n";
        foreach ($this->tools as $name => $tool) {
            $description .= "Tool: $name\n";
            $description .= "Description: " . $tool->get_description() . "\n";
            
            // Show parameter schema in readable format
            // The AI model needs to understand what parameters each tool expects
            $schema = $tool->get_parameters_schema();
            $description .= "Parameters: " . json_encode($schema, JSON_PRETTY_PRINT) . "\n\n";
        }

        return $description;
    }

    /**
     * Executes a specified tool with given parameters
     * 
     * This is where the magic happens - when the AI model says 
     * "I want to use tool X with parameters Y", this method
     * actually makes it happen and returns the result.
     * 
     * @param string $tool_name Name of the tool to execute
     * @param array $params Parameters to pass to the tool
     * @return string Result of tool execution (or error message)
     */
    protected function execute_tool($tool_name, $params) {
        // Verify the tool exists in our registry
        if (!isset($this->tools[$tool_name])) {
            return "Error: Tool '$tool_name' not found. Available tools: " .
                   implode(", ", array_keys($this->tools));
        }

        try {
            // Execute the tool and return its result
            // Each tool implements its own execute() method
            $result = $this->tools[$tool_name]->execute($params);
            return $result;
        } catch (Exception $e) {
            // If something goes wrong, return a helpful error message
            // This helps during development and learning
            return "Error executing tool '$tool_name': " . $e->getMessage();
        }
    }

    /**
     * Debug logging helper
     * 
     * When learning how AI agents work, it's crucial to see what's happening
     * step by step. This method prints debug info when verbose=true.
     * 
     * @param string $message Debug message to print
     */
    protected function log($message) {
        if ($this->verbose) {
            echo "[Agent] " . $message . "\n";
        }
    }
}
