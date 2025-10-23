<?php

/**
 * 🍕 Datapizza-AI PHP - DateTime Tool
 * 
 * Provides date and time operations: current datetime, formatting,
 * and calculating differences between dates.
 * 
 * Educational concepts:
 * - Multi-action tools (one tool, multiple operations)
 * - Timezone handling
 * - Date arithmetic
 * - Flexible date formatting
 * 
 * Why AI needs datetime tools:
 * LLMs are trained on static data and don't know "now".
 * They can't tell you:
 * - What's today's date?
 * - How many days until December 25th?
 * - What time is it in Tokyo?
 */

require_once __DIR__ . '/base_tool.php';

class DateTimeTool extends BaseTool {
    
    public function __construct() {
        $this->name = "datetime";
        $this->description = "Provides date and time information, calculates date differences, formats dates.";
    }
    
    /**
     * Executes datetime operation based on action parameter
     * 
     * Educational pattern - Action dispatch:
     * One tool can perform multiple related operations.
     * The 'action' parameter selects which operation to run.
     * 
     * @param array $params Must contain 'action', other params depend on action
     * @return string Operation result
     */
    public function execute($params = []) {
        $action = $params['action'] ?? 'current';
        
        switch ($action) {
            case 'current':
                return $this->get_current_datetime($params);
            case 'format':
                return $this->format_date($params);
            case 'diff':
                return $this->date_diff($params);
            default:
                return "Unsupported action: $action";
        }
    }
    
    /**
     * Returns current date and time
     * 
     * @param array $params Optional 'format' and 'timezone'
     * @return string Formatted current datetime
     */
    private function get_current_datetime($params) {
        $format = $params['format'] ?? 'Y-m-d H:i:s';
        $timezone = $params['timezone'] ?? 'Europe/Rome';
        
        date_default_timezone_set($timezone);
        return date($format);
    }
    
    /**
     * Formats a given date string
     * 
     * Educational concept - strtotime():
     * PHP's strtotime() is incredibly flexible:
     * - "2024-01-15" → Unix timestamp
     * - "next Friday" → Unix timestamp
     * - "3 days ago" → Unix timestamp
     * 
     * This makes it easy for AI to work with natural date strings.
     * 
     * @param array $params Must contain 'date', optional 'format'
     * @return string Formatted date or error
     */
    private function format_date($params) {
        if (!isset($params['date'])) {
            return "Parameter 'date' required";
        }
        
        $format = $params['format'] ?? 'Y-m-d H:i:s';
        $timestamp = strtotime($params['date']);
        
        if ($timestamp === false) {
            return "Invalid date format: " . $params['date'];
        }
        
        return date($format, $timestamp);
    }
    
    /**
     * Calculates difference between two dates
     * 
     * Educational use case:
     * "How many days until my birthday?" - AI can use this tool
     * to calculate exact difference from today to future date.
     * 
     * @param array $params Must contain 'date1' and 'date2'
     * @return string Human-readable difference
     */
    private function date_diff($params) {
        if (!isset($params['date1']) || !isset($params['date2'])) {
            return "Parameters 'date1' and 'date2' required";
        }
        
        $timestamp1 = strtotime($params['date1']);
        $timestamp2 = strtotime($params['date2']);
        
        if ($timestamp1 === false || $timestamp2 === false) {
            return "Invalid date format";
        }
        
        // Calculate difference in seconds
        $diff_seconds = abs($timestamp2 - $timestamp1);
        
        // Convert to human-readable format
        $days = floor($diff_seconds / 86400);  // 86400 seconds in a day
        $hours = floor(($diff_seconds % 86400) / 3600);
        $minutes = floor(($diff_seconds % 3600) / 60);
        
        return "$days days, $hours hours, $minutes minutes";
    }
    
    /**
     * Returns parameter schema for AI
     * 
     * This schema is more complex because the tool supports
     * multiple actions with different parameters each.
     * 
     * @return array JSON Schema for datetime parameters
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['current', 'format', 'diff'],
                    'description' => 'Operation to perform: current, format, or diff'
                ],
                'format' => [
                    'type' => 'string',
                    'description' => 'Date format string (PHP format, e.g., "Y-m-d H:i:s")'
                ],
                'timezone' => [
                    'type' => 'string',
                    'description' => 'Timezone (e.g., "Europe/Rome", "America/New_York")'
                ],
                'date' => [
                    'type' => 'string',
                    'description' => 'Date string to format'
                ],
                'date1' => [
                    'type' => 'string',
                    'description' => 'First date for difference calculation'
                ],
                'date2' => [
                    'type' => 'string',
                    'description' => 'Second date for difference calculation'
                ]
            ],
            'required' => ['action']
        ];
    }
}
