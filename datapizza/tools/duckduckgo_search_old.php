<?php
/**
 * DuckDuckGoSearchTool - POST method con headers Chrome
 */

require_once __DIR__ . '/base_tool.php';

class DuckDuckGoSearchTool extends BaseTool {
    private $cache_dir;
    
    public function __construct() {
        $this->name = "duckduckgo_search";
        $this->description = "Ricerca web tramite DuckDuckGo (meteo, news, fatti correnti).";
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
    
    public function get_parameters_schema() {
        return [
            'query' => [
                'type' => 'string',
                'description' => 'Query di ricerca',
                'required' => true
            ]
        ];
    }
    
    public function execute($params = []) {
        if (!isset($params['query'])) {
            return "Errore: query richiesta";
        }
        
        $query = trim($params['query']);
        
        // Cache 10 min
        $cache_file = $this->cache_dir . '/ddg_' . md5($query) . '.txt';
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 600) {
            return file_get_contents($cache_file);
        }
        
        // POST con tutti i parametri browser
        $url = "https://lite.duckduckgo.com/lite/";
        $postdata = http_build_query([
            'q' => $query,
            'kl' => 'it-it',
            'df' => ''
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_COOKIE, 'kl=it-it');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: https://lite.duckduckgo.com/',
            'Origin: https://lite.duckduckgo.com',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        ]);
        
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            return "Errore DuckDuckGo (HTTP $http_code)";
        }
        
        // Parse risultati - FIX: apici singoli
        preg_match_all("/<a[^>]*class='result-link'[^>]*href='([^']+)'[^>]*>([^<]+)<\/a>/i", $html, $matches);
        
        if (empty($matches[1])) {
            return "Nessun risultato per: $query";
        }
        
        $output = "Risultati DuckDuckGo per: $query\n\n";
        $count = min(5, count($matches[1]));
        
        for ($i = 0; $i < $count; $i++) {
            $title = html_entity_decode(strip_tags($matches[2][$i]));
            $url = html_entity_decode($matches[1][$i]);
            
            $output .= ($i + 1) . ". $title\n";
            $output .= "   $url\n\n";
        }
        
        file_put_contents($cache_file, $output);
        
        return $output;
    }
}
