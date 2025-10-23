<?php

/**
 * 🍕 Datapizza-AI PHP - DAG Pipeline
 * 
 * Directed Acyclic Graph (DAG) execution engine for complex workflows.
 * 
 * Educational concept - What is a DAG?
 * 
 * DAG = Graph with nodes (tasks) and directed edges (dependencies)
 * "Acyclic" = no loops (A→B→C, not A→B→A)
 * 
 * Example RAG workflow DAG:
 * 
 *     [Parse] → [Split] → [Embed] → [Store]
 *                  ↓
 *             [Rewrite Query]
 * 
 * Why DAG vs simple pipeline?
 * - Conditional execution (skip steps if not needed)
 * - Parallel execution (run independent tasks simultaneously)
 * - Complex dependencies (task C needs A and B to complete)
 * - Reusable workflows (save DAG, run multiple times)
 * 
 * Real-world example - Multi-source RAG:
 * 
 *   [Search Web] ─┐
 *   [Query DB]    ├→ [Merge Results] → [Rank] → [Generate Answer]
 *   [RAG Search] ─┘
 * 
 * All 3 sources run in parallel, then merged!
 */

/**
 * Creates a new DAG structure
 * 
 * Educational structure - DAG representation:
 * 
 * DAG is represented as associative array:
 * array(
 *   'modules' => [
 *     'task_id' => [
 *       'callable' => function or function name,
 *       'params' => parameters,
 *       'dependencies' => [parent_task_ids],
 *       'status' => 'pending|running|complete|failed'
 *     ]
 *   ],
 *   'edges' => [
 *     ['from' => 'A', 'to' => 'B'],
 *     ['from' => 'B', 'to' => 'C']
 *   ]
 * )
 * 
 * @param string $name DAG name (for debugging/logging)
 * @return array Empty DAG structure
 */
function dag_create($name = 'default') {
    return array(
        'name' => $name,
        'modules' => array(),
        'edges' => array()
    );
}

/**
 * Adds a module (task/node) to the DAG
 * 
 * Educational concept - Task definition:
 * 
 * Each module is a callable function with:
 * - Unique ID (e.g., 'parse_doc', 'embed_chunks')
 * - Callable (function name or closure)
 * - Parameters (passed to function when executed)
 * - Dependencies (must complete before this runs)
 * 
 * Example:
 * dag_add_module($dag, 'parse', 'parser_parse_text', ['file' => 'doc.txt']);
 * dag_add_module($dag, 'split', 'splitter_split', ['text' => '$parse'], ['parse']);
 * 
 * '$parse' syntax means "use output from 'parse' task"
 * 
 * @param array $dag DAG structure (passed by reference)
 * @param string $id Unique module ID
 * @param callable $callable Function to execute
 * @param array $params Function parameters
 * @param array $dependencies Array of module IDs this depends on
 */
function dag_add_module(&$dag, $id, $callable, $params = array(), $dependencies = array()) {
    $dag['modules'][$id] = array(
        'callable' => $callable,
        'params' => $params,
        'dependencies' => $dependencies,
        'status' => 'pending',
        'result' => null,
        'error' => null
    );
}

/**
 * Adds a directed edge (dependency) between two modules
 * 
 * Alternative way to define dependencies.
 * Instead of specifying dependencies in dag_add_module(),
 * you can explicitly connect modules:
 * 
 * dag_connect($dag, 'parse', 'split');  // split depends on parse
 * 
 * This makes the DAG structure more visual and readable.
 * 
 * @param array $dag DAG structure
 * @param string $from Source module ID
 * @param string $to Destination module ID
 */
function dag_connect(&$dag, $from, $to) {
    $dag['edges'][] = array('from' => $from, 'to' => $to);
    
    // Update dependencies in module definition
    if (!isset($dag['modules'][$to]['dependencies'])) {
        $dag['modules'][$to]['dependencies'] = array();
    }
    $dag['modules'][$to]['dependencies'][] = $from;
}

/**
 * Executes the DAG (runs all modules in dependency order)
 * 
 * Educational algorithm - Topological sort execution:
 * 
 * 1. Find modules with no dependencies (ready to run)
 * 2. Execute them
 * 3. Mark as complete
 * 4. Repeat until all modules done
 * 
 * Example execution order:
 * 
 * DAG:  A → B → D
 *       A → C → D
 * 
 * Order: A (no deps), then B and C (parallel), then D (waits for B+C)
 * 
 * This implementation is sequential (no parallelization),
 * but structure supports it for future enhancement.
 * 
 * @param array $dag DAG structure
 * @param array $initial_context Initial data to pass to first modules
 * @return array Execution results with all module outputs
 * @throws Exception If circular dependency or execution fails
 */
function dag_run(&$dag, $initial_context = array()) {
    $context = $initial_context;
    $max_iterations = count($dag['modules']) * 2;  // Prevent infinite loops
    $iterations = 0;
    
    while (dag_has_pending_modules($dag)) {
        $iterations++;
        
        // Safety check: prevent infinite loops (indicates circular dependency)
        if ($iterations > $max_iterations) {
            throw new Exception("DAG execution exceeded max iterations. Check for circular dependencies.");
        }
        
        // Find next module ready to execute
        $next_id = dag_next_module($dag);
        
        if ($next_id === null) {
            // No module ready but some still pending = circular dependency!
            throw new Exception("DAG has circular dependencies or unresolved modules");
        }
        
        // Execute module
        $module = &$dag['modules'][$next_id];
        $module['status'] = 'running';
        
        try {
            // Resolve parameters (replace $module_id with actual results)
            $resolved_params = array();
            foreach ($module['params'] as $key => $value) {
                if (is_string($value) && strpos($value, '$') === 0) {
                    // Parameter references another module's output
                    $ref_id = substr($value, 1);
                    if (isset($dag['modules'][$ref_id]['result'])) {
                        $resolved_params[$key] = $dag['modules'][$ref_id]['result'];
                    } else {
                        throw new Exception("Module $next_id references undefined result: $ref_id");
                    }
                } else {
                    $resolved_params[$key] = $value;
                }
            }
            
            // Call the function
            $result = call_user_func_array($module['callable'], $resolved_params);
            
            // Store result
            $module['result'] = $result;
            $module['status'] = 'complete';
            $context[$next_id] = $result;
            
        } catch (Exception $e) {
            $module['status'] = 'failed';
            $module['error'] = $e->getMessage();
            throw new Exception("Module $next_id failed: " . $e->getMessage());
        }
    }
    
    return $context;
}

/**
 * Finds the next module ready to execute
 * 
 * A module is ready if:
 * - Status is 'pending'
 * - All dependencies are 'complete'
 * 
 * @param array $dag DAG structure
 * @return string|null Module ID ready to run, or null if none ready
 */
function dag_next_module($dag) {
    foreach ($dag['modules'] as $id => $module) {
        if ($module['status'] !== 'pending') {
            continue;
        }
        
        // Check if all dependencies are complete
        $ready = true;
        foreach ($module['dependencies'] as $dep_id) {
            if ($dag['modules'][$dep_id]['status'] !== 'complete') {
                $ready = false;
                break;
            }
        }
        
        if ($ready) {
            return $id;
        }
    }
    
    return null;
}

/**
 * Checks if DAG has pending modules
 * 
 * @param array $dag DAG structure
 * @return bool True if any modules are pending or running
 */
function dag_has_pending_modules($dag) {
    foreach ($dag['modules'] as $module) {
        if ($module['status'] === 'pending' || $module['status'] === 'running') {
            return true;
        }
    }
    return false;
}
