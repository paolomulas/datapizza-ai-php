<?php
/**
 * 🍕 Example 1: Your First AI Call from a Raspberry Pi (2011!)
 * 
 * This is where it all begins. You're about to make an AI understand text
 * and generate a response — from a 12-year-old board with 512MB of RAM.
 * 
 * What you'll learn:
 * - How to connect to an LLM (Large Language Model) API
 * - How text goes in, intelligence comes out
 * - Why you don't need a GPU to work with AI
 * 
 * The magic: Your Raspberry Pi just sends text over HTTP.
 * The LLM (running on remote servers) does the heavy thinking.
 * You get back generated text. That's it. That's AI.
 */

//require_once __DIR__ . '/../datapizza/clients/openai.php';
require_once __DIR__ . '/../datapizza/clients/deepseek.php';  // ✅ Changed!

// Step 1: Load API keys from .env file
// (Never hardcode secrets! Always use environment variables)
$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

/* // OpenAI example (commented out - you can switch anytime!)
echo "=== Test OpenAI Client ===\n\n";
echo "Response: " . openai_complete("Write a short funny joke about PHP.", 150, 0.8) . "\n"; */


// Step 2: Make your first AI call
// We're sending a prompt (question/instruction) to DeepSeek's LLM
// Parameters: prompt, max_tokens (length limit), temperature (creativity 0-1)
echo "=== Test DeepSeek Client ===\n\n";
echo "Response: " . deepseek_complete("Write a short funny joke about PHP.", 150, 0.8) . "\n";

/**
 * 🎓 What just happened?
 * 
 * 1. Your PHP script sent an HTTP request with your prompt
 * 2. DeepSeek's servers received it, processed it through a neural network
 * 3. The model predicted the most likely next words, one token at a time
 * 4. The complete response came back as a string
 * 5. You printed it. Done.
 * 
 * No ML libraries. No tensors. No pip install torch.
 * Just plain PHP, making HTTP calls, getting text back.
 * 
 * That's the beauty: AI doesn't have to be complicated.
 * Understanding beats horsepower. 🚀
 */
?>
