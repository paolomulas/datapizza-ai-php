<?php

/**
 * 🍕 Datapizza-AI PHP - Wikipedia Search Tool
 * 
 * Searches Wikipedia using the official MediaWiki API.
 * This is the "proper" way to search Wikipedia vs scraping HTML.
 * 
 * Educational concepts demonstrated:
 * - Official API usage (better than web scraping)
 * - File-based caching (reduce API calls)
 * - Language fallback (IT→EN if no results)
 * - User-Agent best practices
 * - Timeout management for slow networks
 * 
 * Why Wikipedia matters for AI:
 * Wikipedia is the world's largest knowledge base with:
 * - 60+ million articles across 300+ languages
 * - Neutral, factual information
 * - Constantly updated by community
 * - Free API with generous rate limits
 * 
 * Perfect for AI agents that need factual knowledge.
 */

require_once __DIR__ . '/base_tool.php';

class WikipediaSearchTool extends BaseTool {
    
    private $timeout;      // HTTP request timeout in seconds
    private $cache_dir;    // Directory for caching search results
    
    /**
     * Constructor
     * 
     * @param int $timeout HTTP timeout in seconds (default: 10)
     */
    public function __construct($timeout = 10) {
        $this->name = "wikipedia_search";
        $this->description = "Searches Wikipedia for information (facts, definitions, historical data, geography, people).";
        $this->timeout = $timeout;
        
        // Setup cache directory
        $this->cache_dir = __DIR__ . '/../../data/search_cache';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    public function get_name() {
        return $this->name;
    }
    
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Returns parameter schema for AI
     */
    public function get_parameters_schema() {
        return [
            'query' => [
                'type' => 'string',
                'description' => 'Search query',
                'required' => true
            ]
        ];
    }
    
    /**
     * Executes Wikipedia search with caching and language fallback
     * 
     * Educational concept - Caching strategy:
     * Wikipedia content doesn't change often. Caching saves:
     * - API rate limit quota
     * - Network bandwidth (important on Raspberry Pi)
     * - Response time (instant for cached results)
     * 
     * Cache TTL: 1 hour (3600 seconds)
     * 
     * Language fallback strategy:
     * 1. Try Italian Wikipedia first
     * 2. If no results, fallback to English
     * 3. English Wikipedia is larger and more likely to have results
     * 
     * @param array $params Must contain 'query'
     * @return string Formatted search results
     */
    public function execute($params = []) {
        if (!isset($params['query'])) {
            return "Error: parameter 'query' required";
        }
        
        $query = trim($params['query']);
        
        // Check cache (1 hour TTL)
        $cache_file = $this->cache_dir . '/' . md5($query) . '.txt';
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 3600) {
            return file_get_contents($cache_file) . "\n\n[From cache]";
        }
        
        // Search Italian Wikipedia first
        $result_it = $this->search_wikipedia($query, 'it');
        
        // Fallback to English if no Italian results
        if (strpos($result_it, 'No results found') !== false) {
            $result_en = $this->search_wikipedia($query, 'en');
            $result = $result_en;
        } else {
            $result = $result_it;
        }
        
        // Save to cache
        file_put_contents($cache_file, $result);
        
        return $result;
    }
    
    /**
     * Performs Wikipedia API search in specified language
     * 
     * Educational concept - MediaWiki API:
     * Wikipedia uses MediaWiki software with a powerful API.
     * 
     * API endpoint format:
     * https://{lang}.wikipedia.org/w/api.php
     * 
     * Parameters we use:
     * - action=query: We're querying data
     * - format=json: Return results as JSON
     * - list=search: We want search results
     * - srsearch={query}: Search term
     * - srlimit=3: Return top 3 results
     * - srprop=snippet: Include text snippets
     * 
     * User-Agent header:
     * Wikipedia asks that you identify your bot. Good practice!
     * Format: "AppName/Version (Purpose)"
     * 
     * @param string $query Search query
     * @param string $lang Language code ('en', 'it', 'fr', etc.)
     * @return string Formatted results or error message
     */
    private function search_wikipedia($query, $lang = 'it') {
        // Build Wikipedia API URL
        $url = "https://{$lang}.wikipedia.org/w/api.php?" . http_build_query([
            'action' => 'query',
            'format' => 'json',
            'list' => 'search',
            'srsearch' => $query,
            'srlimit' => 3,          // Top 3 results
            'srprop' => 'snippet'     // Include text snippet
        ]);
        
        // Make HTTP request with curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DataPizza-AI-PHP/1.1 (Educational)');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Handle errors
        if ($http_code !== 200 || !$response) {
            return "Search error (HTTP $http_code)";
        }
        
        // Parse JSON response
        $data = json_decode($response, true);
        
        if (empty($data['query']['search'])) {
            return "No results found for: $query";
        }
        
        // Format results for AI consumption
        $output = "Wikipedia results ($lang) for: $query\n\n";
        
        foreach ($data['query']['search'] as $i => $item) {
            $title = $item['title'];
            $snippet = strip_tags($item['snippet']);  // Remove HTML tags
            $url = "https://{$lang}.wikipedia.org/wiki/" . urlencode(str_replace(' ', '_', $title));
            
            $output .= ($i + 1) . ". {$title}\n";
            $output .= "   {$snippet}...\n";
            $output .= "   {$url}\n\n";
        }
        
        return trim($output);
    }
}
