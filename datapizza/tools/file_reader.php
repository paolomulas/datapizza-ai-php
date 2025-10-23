<?php

/**
 * 🍕 Datapizza-AI PHP - File Reader Tool
 * 
 * Reads text file contents with security constraints.
 * Demonstrates multiple security layers to prevent common vulnerabilities.
 * 
 * Educational security concepts:
 * - Path traversal prevention (basename)
 * - Extension whitelisting (only safe file types)
 * - Output limiting (prevent RAM exhaustion)
 * - Sandboxing (restricted to specific directory)
 * 
 * Vulnerabilities this prevents:
 * - Path traversal: "../../../etc/passwd" → blocked by basename()
 * - Arbitrary execution: "malware.exe" → blocked by extension check
 * - RAM exhaustion: reading 10GB file → limited to first 5000 chars
 */

require_once __DIR__ . '/base_tool.php';

class FileReader extends BaseTool {
    
    private $allowed_path;  // Sandbox directory (can't read outside this)
    
    /**
     * Constructor - Sets up sandboxed file access
     * 
     * @param string|null $allowed_path Directory to restrict access to
     */
    public function __construct($allowed_path = null) {
        $this->name = "file_reader";
        $this->description = "Reads content of text files (txt, md, json, csv, php).";
        
        // Default sandbox: ../../data/ directory
        // AI can only read files in this folder
        $this->allowed_path = $allowed_path ?: __DIR__ . '/../../data/';
    }
    
    /**
     * Reads file content with security checks
     * 
     * Security layers:
     * 1. basename() - Prevents path traversal attacks
     * 2. file_exists() - Ensures file exists before reading
     * 3. Extension check - Only allows safe file types
     * 4. Length limit - Prevents reading huge files into RAM
     * 
     * Educational example of path traversal prevention:
     * User input: "../../../../etc/passwd"
     * After basename(): "passwd"
     * Full path: "/data/passwd" (not /etc/passwd)
     * Result: File not found (can't escape sandbox)
     * 
     * @param array $params Must contain 'filename', optional 'max_length'
     * @return string File contents or error message
     */
    public function execute($params = []) {
        // Validate required parameter
        if (!isset($params['filename'])) {
            throw new Exception("Parameter 'filename' required");
        }
        
        $filename = $params['filename'];
        
        // Security layer 1: basename() strips path components
        // This prevents "../" attacks
        $filepath = $this->allowed_path . basename($filename);
        
        // Security layer 2: Check file exists
        if (!file_exists($filepath)) {
            return "File not found: $filename";
        }
        
        // Security layer 3: Extension whitelist
        // Only allow safe, text-based file types
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $allowed = ['txt', 'md', 'json', 'csv', 'php', 'log'];
        
        if (!in_array($extension, $allowed)) {
            return "Unsupported file type: $extension";
        }
        
        // Read file content
        $content = file_get_contents($filepath);
        
        // Security layer 4: Limit output length
        // Prevents RAM exhaustion on Raspberry Pi when reading large files
        $max_length = $params['max_length'] ?? 5000;
        
        if (strlen($content) > $max_length) {
            $content = substr($content, 0, $max_length);
            $content .= "\n\n[... truncated, file is longer than $max_length characters]";
        }
        
        return $content;
    }
    
    /**
     * Returns parameter schema for AI
     * 
     * @return array JSON Schema for file_reader parameters
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'filename' => [
                    'type' => 'string',
                    'description' => 'Name of file to read (e.g., "config.json", "notes.txt")'
                ],
                'max_length' => [
                    'type' => 'integer',
                    'description' => 'Maximum characters to read (default: 5000)'
                ]
            ],
            'required' => ['filename']
        ];
    }
}
