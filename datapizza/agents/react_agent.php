<?php

/**
 * 🍕 Datapizza-AI PHP - ReAct Agent
 * 
 * This implements the ReAct pattern (Reasoning + Acting).
 * The agent thinks step-by-step, uses tools when needed, and
 * continues until it has enough information to answer.
 * 
 * Educational concepts demonstrated:
 * - ReAct pattern: Thought -> Action -> Observation loops
 * - LLM prompting strategies for structured reasoning
 * - Error handling for malformed AI responses
 * - Temperature control for precise vs creative responses
 */

require_once 'base_agent.php';
require_once __DIR__ . '/../clients/dpz_call.php';

class ReactAgent extends BaseAgent {
    
    /**
     * Main execution method - implements the ReAct reasoning loop
     * 
     * The ReAct pattern works like this:
     * 1. AI thinks about what to do (Thought)
     * 2. AI chooses an action/tool to use (Action + Input)
     * 3. We execute the tool and show result (Observation)
     * 4. Repeat until AI has enough info for Final Answer
     * 
     * @param string $query User's question or request
     * @return string Final answer after reasoning process
     */
    public function run($query) {
        $this->log("Received query: $query");

        // Build system prompt that explains available tools and ReAct format
        $system_prompt = $this->build_system_prompt();

        // Initialize conversation history
        // This grows with each iteration, giving the AI context
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $query]
        ];

        // ReAct loop - limited by max_iterations to prevent infinite loops
        for ($iteration = 0; $iteration < $this->max_iterations; $iteration++) {
            $this->log("Iteration " . ($iteration + 1) . "/" . $this->max_iterations);

            // Call the AI model to get next reasoning step
            $response = $this->call_llm($messages);
            $this->log("LLM Response:\n$response");

            // Add AI's response to conversation history
            $messages[] = ['role' => 'assistant', 'content' => $response];

            // Check if AI provided final answer
            if ($this->has_final_answer($response)) {
                $final_answer = $this->extract_final_answer($response);
                $this->log("Final Answer found: $final_answer");
                return $final_answer;
            }

            // Parse the AI's response to extract action and parameters
            $action_data = $this->parse_action($response);
            if ($action_data === null) {
                // AI didn't follow the expected format - ask for correction
                $this->log("Invalid response format, requesting correction");
                $messages[] = [
                    'role' => 'user',
                    'content' => 'Please follow the correct format: Thought/Action/Input or Final Answer'
                ];
                continue;
            }

            // Execute the tool the AI requested
            $tool_name = $action_data['tool'];
            $params = $action_data['params'];
            $this->log("Executing tool: $tool_name with params: " . json_encode($params));
            
            $observation = $this->execute_tool($tool_name, $params);
            $this->log("Observation: $observation");

            // Add tool result to conversation so AI can see it
            $messages[] = [
                'role' => 'user',
                'content' => "Observation: $observation"
            ];
        }

        // If we reach here, we hit the iteration limit
        return "I'm sorry, I couldn't find an answer after " . 
               $this->max_iterations . " attempts.";
    }

    /**
     * Builds the system prompt that teaches the AI the ReAct format
     * 
     * This is crucial - the quality of this prompt determines how well
     * the AI follows the ReAct pattern. We provide clear instructions
     * and concrete examples.
     * 
     * @return string Complete system prompt with ReAct instructions
     */
    private function build_system_prompt() {
        $tools_desc = $this->build_tools_description();
        
        return "You are an AI assistant that uses the ReAct pattern to answer questions.

$tools_desc

To answer, follow this format:
Thought: [your reasoning about what to do]
Action: [name of tool to use]  
Input: [JSON parameters for the tool]

After receiving the observation, you can:
- Continue with another Thought/Action/Input if you need more information
- Give the final answer with: Final Answer: [your complete response]

Important rules:
1. Always think before acting (Thought is mandatory)
2. Use only one tool at a time
3. Wait for the Observation before proceeding  
4. When you have all necessary info, use Final Answer

Example:
User: How many days until Christmas?

Thought: I need to calculate the difference between today and December 25th, 2025
Action: datetime
Input: {\"action\": \"diff\", \"date1\": \"2025-10-14\", \"date2\": \"2025-12-25\"}

[you receive observation]
Observation: 72 days, 0 hours, 0 minutes

Thought: I have the answer, I can provide it to the user
Final Answer: There are 72 days until Christmas!";
    }

    /**
     * Calls the AI model to get the next reasoning step
     * 
     * This handles the HTTP API call to whatever AI service we're using.
     * We use temperature=0.0 for precise reasoning (no creativity needed).
     * 
     * @param array $messages Conversation history so far
     * @return string AI model's response
     */
    private function call_llm($messages) {
        // Different providers use different endpoints
        // PHP 7.4: Use if/else instead of match expression
        if ($this->llm_provider === 'openai') {
            $endpoint = 'v1/chat/completions';
        } elseif ($this->llm_provider === 'deepseek') {
            $endpoint = 'chat/completions';
        } else {
            $endpoint = 'v1/chat/completions';
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.0  // Low temperature for precise reasoning
        ];

        try {
            $result = dpz_call($this->llm_provider, $endpoint, $payload);
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            } else {
                throw new Exception("Unexpected LLM response: " . json_encode($result));
            }
        } catch (Exception $e) {
            throw new Exception("LLM call error: " . $e->getMessage());
        }
    }

    /**
     * Checks if the response contains a Final Answer
     * 
     * @param string $response AI model's response
     * @return bool True if contains Final Answer
     */
    private function has_final_answer($response) {
        return preg_match('/Final Answer:/i', $response) === 1;
    }

    /**
     * Extracts the Final Answer from the response
     * 
     * @param string $response AI model's response  
     * @return string Extracted final answer
     */
    private function extract_final_answer($response) {
        if (preg_match('/Final Answer:\s*(.+)/is', $response, $matches)) {
            return trim($matches[1]);
        }
        return trim($response);
    }

    /**
     * Parses AI response to extract Action and Input parameters
     * 
     * This is where we handle the structured format the AI should follow.
     * If the AI doesn't follow the format correctly, we return null
     * and ask for correction in the main loop.
     * 
     * @param string $response AI model's response
     * @return array|null Array with 'tool' and 'params', or null if parsing fails
     */
    private function parse_action($response) {
        // Look for pattern: Action: tool_name
        if (!preg_match('/Action:\s*(\w+)/i', $response, $action_match)) {
            return null;
        }

        $tool_name = trim($action_match[1]);
        
        // Look for pattern: Input: {...}
        if (preg_match('/Input:\s*(\{.*?\})/s', $response, $input_match)) {
            $json_string = trim($input_match[1]);
            $params = json_decode($json_string, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log("JSON parsing error: " . json_last_error_msg());
                return null;
            }
            
            return [
                'tool' => $tool_name,
                'params' => $params
            ];
        }

        // If no Input specified, use empty array
        return [
            'tool' => $tool_name,
            'params' => []
        ];
    }
}
