<?php

/**
 * 🍕 Datapizza-AI PHP - Base Tool
 * 
 * Abstract foundation for all AI agent tools.
 * Tools extend AI capabilities by allowing them to interact with
 * external systems, APIs, databases, and services.
 * 
 * Educational concepts:
 * - Abstract classes define contracts without implementation
 * - Tools are discoverable via get_parameters_schema()
 * - JSON Schema describes parameters for LLMs
 * - Consistent interface enables dynamic tool loading
 * 
 * Why tools matter for AI:
 * Without tools, AI can only generate text. With tools:
 * - Calculate: 1523 * 847 = ? (math tool)
 * - Search: Latest news on quantum computing (web search tool)
 * - Read: What's in config.json? (file reader tool)
 * - Time: How many days until Christmas? (datetime tool)
 */

abstract class BaseTool {
    protected $name;         // Tool identifier (used by AI to call tool)
    protected $description;  // Human-readable explanation of what tool does
    
    /**
     * Executes the tool with given parameters
     * 
     * This is the core method where tool logic lives.
     * Each tool implements its own execute() method.
     * 
     * Educational note:
     * The AI model calls this method via the agent. The agent
     * doesn't need to understand what the tool does - it just
     * passes parameters and returns the result.
     * 
     * @param array $params Parameters from AI (format defined by get_parameters_schema)
     * @return string Result to send back to AI
     */
    abstract public function execute($params = []);
    
    /**
     * Returns tool name
     * 
     * The AI uses this name to specify which tool to call.
     * Example: "Action: calculator" in ReAct pattern.
     * 
     * @return string Tool name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Returns human-readable tool description
     * 
     * This description is shown to the AI in the system prompt,
     * helping it understand when and how to use this tool.
     * 
     * @return string Tool description
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Returns JSON Schema describing tool parameters
     * 
     * This is crucial for AI integration. The schema tells the LLM:
     * - What parameters this tool accepts
     * - What type each parameter is (string, number, boolean, etc.)
     * - Which parameters are required
     * - Example values or descriptions
     * 
     * Educational concept - JSON Schema:
     * JSON Schema is a standard for describing data structures.
     * LLMs are trained to understand and generate valid JSON Schema,
     * making tools self-documenting and discoverable.
     * 
     * Format:
     * {
     *   "type": "object",
     *   "properties": {
     *     "param1": {"type": "string", "description": "..."},
     *     "param2": {"type": "number", "description": "..."}
     *   },
     *   "required": ["param1"]
     * }
     * 
     * @return array JSON Schema as associative array
     */
    abstract public function get_parameters_schema();
}
