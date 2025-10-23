<?php

/**
 * 🍕 Datapizza-AI PHP - Chat Prompt Templates
 * 
 * Pre-built prompt templates for common AI tasks.
 * 
 * Educational concept - Prompt engineering:
 * The way you ask a question dramatically affects the answer!
 * 
 * Bad prompt: "What is API?"
 * Good prompt: "You are a technical documentation expert. 
 *               Explain what an API is in simple terms, with examples."
 * 
 * Prompt components:
 * 1. Role: "You are a [expert/assistant/teacher]..."
 * 2. Task: "Your task is to..."
 * 3. Context: "Here is relevant information: ..."
 * 4. Constraints: "Answer in 3 sentences. Use simple language."
 * 5. Format: "Format as: 1. ... 2. ... 3. ..."
 * 
 * Why templates?
 * - Consistency: Same format every time
 * - Best practices: Pre-tested prompts
 * - Reusability: Don't rewrite prompts
 * - Educational: Learn good prompt patterns
 */

/**
 * Generic prompt formatter
 * 
 * Builds a prompt from structured components.
 * This is the foundation for all other templates.
 * 
 * @param string $role System role (e.g., "helpful assistant")
 * @param string $task User task/question
 * @param string $context Optional context (RAG docs, examples, etc.)
 * @param string $constraints Optional constraints (length, format, tone)
 * @return string Formatted prompt
 */
function prompt_format($role, $task, $context = '', $constraints = '') {
    $prompt = '';
    
    // Add role
    if (!empty($role)) {
        $prompt .= "You are a $role.\n\n";
    }
    
    // Add context (if provided)
    if (!empty($context)) {
        $prompt .= "Context:\n$context\n\n";
    }
    
    // Add task
    $prompt .= "Task: $task\n";
    
    // Add constraints (if provided)
    if (!empty($constraints)) {
        $prompt .= "\nConstraints: $constraints\n";
    }
    
    return $prompt;
}

/**
 * RAG (Retrieval-Augmented Generation) prompt template
 * 
 * Educational concept - RAG prompting:
 * RAG = Give the AI relevant documents, then ask question
 * 
 * Flow:
 * 1. Retrieve: Search vectorstore for relevant docs
 * 2. Format: Put docs in prompt as context
 * 3. Generate: LLM answers using context
 * 
 * Why this works:
 * - LLM has concrete info to reference
 * - Reduces hallucinations (making up facts)
 * - Provides source attribution
 * 
 * @param string $query User's question
 * @param string $context Retrieved documents (formatted)
 * @return string RAG prompt
 */
function prompt_rag($query, $context) {
    $prompt = <<<EOT
You are a helpful assistant that answers questions based on provided context.

Context:
$context

Question: $query

Instructions:
- Answer the question using ONLY information from the context above
- If the context doesn't contain enough information, say "I don't have enough information to answer that"
- Cite which document you're referencing (e.g., "According to [1]...")
- Be concise but complete

Answer:
EOT;
    
    return $prompt;
}

/**
 * Simple chat prompt template
 * 
 * For general conversation without RAG.
 * 
 * @param string $user_message User's message
 * @param string $system_prompt Optional system prompt
 * @return array OpenAI-compatible messages array
 */
function prompt_chat($user_message, $system_prompt = 'You are a helpful assistant.') {
    return array(
        array('role' => 'system', 'content' => $system_prompt),
        array('role' => 'user', 'content' => $user_message)
    );
}

/**
 * Advanced RAG prompt with multi-query support
 * 
 * Educational concept - Iterative RAG:
 * Sometimes one query isn't enough!
 * 
 * Example:
 * User: "Compare Python and PHP"
 * 
 * Multi-query approach:
 * 1. Query vectorstore: "Python features"
 * 2. Query vectorstore: "PHP features"
 * 3. Combine results in prompt
 * 4. LLM compares using both sets of docs
 * 
 * Better results than single query!
 * 
 * @param string $query Main user question
 * @param array $contexts Array of context groups (multi-query results)
 * @param array $sub_queries Array of sub-queries that generated contexts
 * @return string Advanced RAG prompt
 */
function prompt_rag_advanced($query, $contexts, $sub_queries = array()) {
    $prompt = "You are an expert assistant that synthesizes information from multiple sources.\n\n";
    
    // Add each context group
    foreach ($contexts as $i => $context) {
        $num = $i + 1;
        $sub_q = isset($sub_queries[$i]) ? $sub_queries[$i] : "Query $num";
        
        $prompt .= "Context $num (for: $sub_q):\n";
        $prompt .= "$context\n\n";
    }
    
    $prompt .= "Main Question: $query\n\n";
    $prompt .= "Instructions:\n";
    $prompt .= "- Synthesize information from all context groups\n";
    $prompt .= "- Compare and contrast when relevant\n";
    $prompt .= "- Cite sources using [Context N] notation\n";
    $prompt .= "- Provide a comprehensive, well-organized answer\n\n";
    $prompt .= "Answer:\n";
    
    return $prompt;
}
