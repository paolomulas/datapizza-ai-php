<?php

/**
 * 🍕 Datapizza-AI PHP - DuckDuckGo Search Tool
 * 
 * Performs web searches by scraping DuckDuckGo search results.
 * 
 * Educational note - HTML Scraping:
 * This is a "fragile" approach - if DuckDuckGo changes their HTML,
 * this tool breaks. Better alternatives exist (SerpAPI, Brave Search API)
 * but require API keys and cost money.
 * 
 * This demonstrates web scraping as a learning exercise.
 * For production, use proper APIs.
 * 
 * Why web search matters for AI:
 * LLMs have a knowledge cutoff date. They can't tell you:
 * - Latest news ("What happened today?")
 * - Current data ("What's Bitcoin price?")
 * - Recent events ("Who won yesterday's game?")
 * 
 * Web search tools give AI access to real-time information.
 */

/**
 * Performs DuckDuckGo search via HTML scraping
 * 
 * Educational concept - Regex on HTML:
 * We use regex to find links in DuckDuckGo's HTML.
 * Pattern: /uddg=([^"&\s]+)/ finds encoded URLs
 * Pattern: >([^<]{5,})<\/a> finds link text
 * 
 * Limitations:
 * - Fragile (breaks if HTML changes)
 * - No API rate limits or error handling
 * - Returns basic info (title + URL, no snippets)
 * 
 * @param string $query Search query
 * @param int $max_results Maximum results to return
 * @return array Array of results [{title, url}, ...]
 */
function tool_duckduckgo_search($query, $max_results = 5) {
    // Build search URL
    $url = 'https://duckduckgo.com/html/?q=' . urlencode($query);
    
    // Set user agent (some sites block requests without it)
    $opts = array(
        'http' => array(
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\n"
        )
    );
    
    // Fetch HTML
    $context = stream_context_create($opts);
    $html = @file_get_contents($url, false, $context);
    
    if ($html === false) {
        return array('error' => 'Failed to fetch search results');
    }
    
    $results = array();
    
    // Ultra-simple pattern: find all links with 'uddg' parameter
    // DDG encodes real URLs in 'uddg=' parameter to track clicks
    if (preg_match_all('/uddg=([^"&\s]+)[^>]*>([^<]{5,})<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
        $count = 0;
        
        foreach ($matches as $match) {
            if ($count >= $max_results) break;
            
            $url_encoded = $match[1];
            $title = trim($match[2]);
            
            // Decode URL
            $real_url = urldecode($url_encoded);
            
            // Add result without filtering
            $results[] = array(
                'title' => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                'url' => $real_url,
                'snippet' => ''  // DDG HTML scraping doesn't easily provide snippets
            );
            
            $count++;
        }
    }
    
    return $results;
}

/**
 * Returns tool description for AI
 * 
 * @return string Tool description
 */
function tool_duckduckgo_search_description() {
    return "Web search via DuckDuckGo";
}
