<?php
/**
 * 🍕 Datapizza-AI PHP - DAG Pipeline
 * 
 * DAG = Directed Acyclic Graph (assembly line workflow)
 * Educational: simplified version to understand the concept
 * 
 * @package DataPizza-AI-PHP
 * @version 1.0
 */

/**
 * Create new empty pipeline
 * 
 * @return array Pipeline structure
 */
function dag_create() {
    return array(
        'modules' => array(),     // Registered modules (name => callable)
        'connections' => array(), // Connections (from => to)
        'executed' => array()     // Execution results
    );
}

/**
 * Add module to pipeline
 * 
 * A "module" is simply a function that:
 * - Takes input
 * - Does something
 * - Returns output
 * 
 * @param array $pipeline Pipeline
 * @param string $name Module name (e.g., "embedder")
 * @param callable $function Function to execute
 * @return array Updated pipeline
 */
function dag_add_module($pipeline, $name, $function) {
    $pipeline['modules'][$name] = $function;
    return $pipeline;
}

/**
 * Connect two modules
 * 
 * Meaning: "the output of FROM becomes input of TO"
 * 
 * @param array $pipeline Pipeline
 * @param string $from Source module name
 * @param string $to Destination module name
 * @return array Updated pipeline
 */
function dag_connect($pipeline, $from, $to) {
    if (!isset($pipeline['connections'][$from])) {
        $pipeline['connections'][$from] = array();
    }
    $pipeline['connections'][$from][] = $to;
    return $pipeline;
}

/**
 * Execute pipeline (simple sequential version)
 * 
 * Educational: executes modules in connection order
 * (advanced version would do topological sort)
 * 
 * @param array $pipeline Pipeline
 * @param string $start_module Initial module
 * @param mixed $input Initial input
 * @return mixed Final output
 */
function dag_run($pipeline, $start_module, $input) {
    $current_output = $input;
    $current_module = $start_module;
    
    echo "🚀 DAG Pipeline Started\n";
    echo str_repeat("═", 50) . "\n\n";
    
    // Execute modules in sequence
    $executed = array();
    
    while ($current_module !== null) {
        // Execute current module
        if (isset($pipeline['modules'][$current_module])) {
            echo "📦 Executing: $current_module\n";
            
            $function = $pipeline['modules'][$current_module];
            $current_output = call_user_func($function, $current_output);
            
            $executed[$current_module] = $current_output;
            echo "  ✓ Output: " . dag_preview_output($current_output) . "\n\n";
        }
        
        // Find next connected module
        $current_module = dag_next_module($pipeline, $current_module);
    }
    
    echo str_repeat("═", 50) . "\n";
    echo "✅ Pipeline Complete!\n\n";
    
    return $current_output;
}

/**
 * Find next connected module (helper)
 */
function dag_next_module($pipeline, $current) {
    if (isset($pipeline['connections'][$current])) {
        $next_modules = $pipeline['connections'][$current];
        return $next_modules[0]; // Take first connected (simplified)
    }
    return null;
}

/**
 * Preview output for debug (helper)
 */
function dag_preview_output($output) {
    if (is_string($output)) {
        return substr($output, 0, 50) . (strlen($output) > 50 ? '...' : '');
    } elseif (is_array($output)) {
        return count($output) . ' items';
    } else {
        return gettype($output);
    }
}
