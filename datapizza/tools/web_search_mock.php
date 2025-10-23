<?php

/**
 * 🍕 Datapizza-AI PHP - Mock Web Search Tool
 * 
 * Mock web search for testing without real API calls.
 * Returns predefined results based on keyword matching.
 * 
 * Educational purpose:
 * This tool demonstrates how to build mock implementations for testing.
 * Real web search APIs (SerpAPI, Brave Search) cost money and have rate limits.
 * Mock tools let you:
 * - Develop and test without API keys
 * - Run tests without network dependency
 * - Demonstrate features in presentations/demos
 * - Learn the pattern before implementing real API
 * 
 * When to use mock vs real:
 * - Development/Testing: Use mock
 * - Production: Use real API (SerpAPI, Brave, Bing, etc.)
 * - Demos: Use mock (predictable results)
 * 
 * This is a common pattern in software development called "mocking".
 */

/**
 * Performs mock web search with hardcoded results
 * 
 * Educational concept - Mock data:
 * We maintain a small "database" of results for common queries.
 * Keywords are matched case-insensitively in the query.
 * 
 * Example:
 * Query "best PHP frameworks" → matches "php" keyword
 * Returns: PHP docs, PHP The Right Way, Laravel
 * 
 * @param string $query Search query
 * @param int $max_results Maximum results to return
 * @return array Array of results [{title, url, snippet}, ...]
 */
function tool_web_search_mock($query, $max_results = 5) {
    // Mock database - hardcoded results for common topics
    // In a real implementation, this would be API calls to Google, Bing, etc.
    $mock_db = array(
        'php' => array(
            array(
                'title' => 'PHP Official Documentation',
                'url' => 'https://php.net/docs',
                'snippet' => 'Official PHP manual and documentation'
            ),
            array(
                'title' => 'PHP The Right Way',
                'url' => 'https://phptherightway.com',
                'snippet' => 'Best practices guide for modern PHP'
            ),
            array(
                'title' => 'Laravel Framework',
                'url' => 'https://laravel.com',
                'snippet' => 'Popular PHP framework for web applications'
            )
        ),
        'ai' => array(
            array(
                'title' => 'OpenAI Platform',
                'url' => 'https://platform.openai.com',
                'snippet' => 'Build AI applications with GPT models'
            ),
            array(
                'title' => 'DataPizza AI Framework',
                'url' => 'https://datapizza.tech',
                'snippet' => 'Open source AI framework for developers'
            ),
            array(
                'title' => 'Anthropic Claude',
                'url' => 'https://anthropic.com',
                'snippet' => 'Advanced AI assistant'
            )
        ),
        'raspberry' => array(
            array(
                'title' => 'Raspberry Pi Official',
                'url' => 'https://raspberrypi.org',
                'snippet' => 'Official Raspberry Pi website and projects'
            ),
            array(
                'title' => 'Raspberry Pi Projects',
                'url' => 'https://projects.raspberrypi.org',
                'snippet' => 'Community project ideas and tutorials'
            ),
            array(
                'title' => 'MagPi Magazine',
                'url' => 'https://magpi.cc',
                'snippet' => 'Free Raspberry Pi magazine'
            )
        )
    );
    
    // Match query with keywords (case-insensitive)
    $query_lower = strtolower($query);
    $results = array();
    
    foreach ($mock_db as $keyword => $entries) {
        if (strpos($query_lower, $keyword) !== false) {
            $results = $entries;
            break;
        }
    }
    
    // Default results if no keyword match
    // This ensures the tool always returns something
    if (empty($results)) {
        $results = array(
            array(
                'title' => 'Search Result for: ' . $query,
                'url' => 'https://example.com/search',
                'snippet' => 'Mock result for educational purposes'
            ),
            array(
                'title' => 'Related Topic',
                'url' => 'https://example.com/related',
                'snippet' => 'Additional mock result'
            )
        );
    }
    
    // Limit to max_results
    return array_slice($results, 0, $max_results);
}

/**
 * Returns tool description for AI
 */
function tool_web_search_mock_description() {
    return "Mock web search for testing (no real API calls)";
}
