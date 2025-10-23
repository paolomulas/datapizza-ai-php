<?php

/**
 * 🍕 Datapizza-AI PHP - Base Parser Documentation
 * 
 * Parsers follow a procedural pattern for simplicity.
 * No classes, no inheritance - just simple functions!
 * 
 * Educational concept - Procedural vs OOP:
 * This framework uses procedural style because:
 * - Simpler to understand (no $this, new, extends)
 * - Easier to debug (just functions)
 * - Perfect for Raspberry Pi (less memory overhead)
 * - PHP 7.x compatible (no modern OOP features needed)
 * 
 * Parser function naming convention:
 * parser_parse_TYPE($filepath)
 * 
 * Examples:
 * - parser_parse_text($filepath)
 * - parser_parse_json($filepath)
 * - parser_parse_csv($filepath)
 * 
 * Standard return format:
 * array(
 *   'text' => 'extracted content',
 *   'metadata' => array(
 *     'filename' => '...',
 *     'type' => '...',
 *     'size_bytes' => ...,
 *     ...
 *   )
 * )
 * 
 * Why standard format?
 * All parsers return the same structure, making them
 * interchangeable. The calling code doesn't need to know
 * if it's parsing text, JSON, or CSV - just use the result!
 * 
 * @package DataPizza-AI-PHP
 * @version 1.2
 */

// No code here - this is just documentation!
// See individual parser files for implementations.
