<?php

/**
 * 🍕 Datapizza-AI PHP - Calculator Tool
 * 
 * Performs mathematical calculations using safe expression evaluation.
 * Demonstrates how to safely use eval() with input sanitization.
 * 
 * Educational concepts:
 * - Input sanitization (security best practice)
 * - Safe eval() usage (whitelist approach)
 * - Error handling for invalid expressions
 * - Tool parameter schema definition
 * 
 * Why calculators are essential for AI:
 * LLMs are trained on text, not arithmetic. They often get
 * calculations wrong (try asking GPT-4 "what's 1523 * 847?").
 * A calculator tool gives precise results every time.
 */

require_once __DIR__ . '/base_tool.php';

class Calculator extends BaseTool {
    
    public function __construct() {
        $this->name = "calculator";
        $this->description = "Performs mathematical calculations. Supports +, -, *, /, %, sqrt, pow, abs.";
    }
    
    /**
     * Executes mathematical calculation
     * 
     * Educational security concept:
     * eval() is dangerous if used with unsanitized input. Someone could inject:
     * "system('rm -rf /')" and delete your entire system!
     * 
     * Our solution: Whitelist allowed characters + safe function conversion.
     * 
     * @param array $params Must contain 'expression' key
     * @return string Calculation result or error message
     */
    public function execute($params = []) {
        // Validate required parameter
        if (!isset($params['expression'])) {
            throw new Exception("Parameter 'expression' required");
        }
        
        $expression = $params['expression'];
        
        // Convert safe functions to PHP equivalents
        // sqrt(x) → pow(x, 0.5) for better compatibility
        $expression = preg_replace('/sqrt\s*\(\s*([^)]+)\s*\)/', 'pow($1, 0.5)', $expression);
        
        // pow(x, y) stays as is (already PHP function)
        // abs(x) stays as is (already PHP function)
        
        // Security: Sanitize input by removing any non-math characters
        // Whitelist: numbers, operators, parentheses, spaces, %, and letters (for pow/abs)
        $expression = preg_replace('/[^0-9+\-*\/\.\(\)\s%,powabs]/', '', $expression);
        
        try {
            // Safe eval() - only math operations allowed
            // @ suppresses warnings for cleaner error handling
            $result = @eval("return $expression;");
            
            if ($result === false || $result === null) {
                return "Calculation error: Invalid expression";
            }
            
            return "Result: " . $result;
            
        } catch (Exception $e) {
            // Handle invalid expressions (division by zero, syntax errors, etc.)
            return "Calculation error: " . $e->getMessage();
        }
    }
    
    /**
     * Returns parameter schema for AI
     * 
     * This tells the LLM:
     * - The tool expects one parameter called 'expression'
     * - It should be a string
     * - It's a math expression like "2+2" or "sqrt(144)"
     * - It's required (AI must provide it)
     * 
     * @return array JSON Schema for calculator parameters
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'expression' => [
                    'type' => 'string',
                    'description' => 'Mathematical expression to calculate (e.g., "2+2", "15*3", "sqrt(144)", "pow(2,3)")'
                ]
            ],
            'required' => ['expression']
        ];
    }
}
