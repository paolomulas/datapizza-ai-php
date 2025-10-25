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

// ============================================
// 🔧 STEP 1: CHOOSE YOUR CLIENT
// ============================================
// Uncomment ONE of the following:

require_once __DIR__ . '/../../datapizza/clients/openai.php';      // ✅ OpenAI (GPT-4o-mini)
// require_once __DIR__ . '/../../datapizza/clients/deepseek.php';   // DeepSeek
// require_once __DIR__ . '/../../datapizza/clients/claude.php';     // Anthropic Claude
// require_once __DIR__ . '/../../datapizza/clients/gemini.php';     // Google Gemini
// require_once __DIR__ . '/../../datapizza/clients/mistral.php';    // Mistral AI
// require_once __DIR__ . '/../../datapizza/clients/kimi.php';       // Moonshot Kimi

// ============================================
// 🔑 STEP 2: LOAD API KEYS
// ============================================
// All API keys are read from .env file
// Make sure you have the correct key for your chosen client!
//
// Required .env keys:
// - OpenAI:  OPENAI_API_KEY
// - DeepSeek: DEEPSEEK_API_KEY
// - Claude:  CLAUDE_API_KEY
// - Gemini:  GEMINI_API_KEY
// - Mistral: MISTRAL_API_KEY
// - Kimi:    KIMI_API_KEY

$env = parse_ini_file(__DIR__ . '/../../.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

// ============================================
// 🚀 STEP 3: CALL THE LLM
// ============================================
// Uncomment the ONE that matches your client above:

$client_name = "OpenAI (GPT-4o-mini)";
$response = openai_complete("Write a short funny joke about PHP.", 150, 0.8);

// $client_name = "DeepSeek";
// $response = deepseek_complete("Write a short funny joke about PHP.", 150, 0.8);

// $client_name = "Claude (Anthropic)";
// $response = claude_complete("Write a short funny joke about PHP.", 150, 0.8);

// $client_name = "Gemini (Google)";
// $response = gemini_complete("Write a short funny joke about PHP.", 150, 0.8);

// $client_name = "Mistral AI";
// $response = mistral_complete("Write a short funny joke about PHP.", 150, 0.8);

// $client_name = "Kimi (Moonshot)";
// $response = kimi_complete("Write a short funny joke about PHP.", 150, 0.8);

// ============================================
// 📺 DISPLAY RESULT
// ============================================
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     🍕 Your First AI Call from Raspberry Pi       ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "🤖 Client: $client_name\n";
echo "📝 Prompt: Write a short funny joke about PHP.\n\n";
echo "💬 Response:\n";
echo str_repeat("─", 54) . "\n";
echo $response . "\n";
echo str_repeat("─", 54) . "\n\n";

echo "✅ Success! You just made an AI call from a 2011 board! 🎉\n";

/**
 * 🎓 What just happened?
 * 
 * THE FLOW:
 * ─────────
 * 1. Your PHP script sent an HTTP POST request to the LLM API
 * 2. The LLM server received your prompt
 * 3. Neural network predicted the next words, token by token
 * 4. Complete response came back as JSON
 * 5. You extracted and printed the text
 * 
 * PARAMETERS EXPLAINED:
 * ────────────────────
 * function_complete(prompt, max_tokens, temperature)
 * 
 * - prompt: Your instruction/question for the AI
 * - max_tokens: Maximum length of response (1 token ≈ 0.75 words)
 * - temperature: Creativity level (0 = deterministic, 1 = creative)
 * 
 * ALL CLIENTS HAVE THE SAME API!
 * ──────────────────────────────
 * Every client implements: client_complete(prompt, max_tokens, temperature)
 * 
 * This means switching between providers is TRIVIAL:
 * 1. Change the require_once line (Step 1)
 * 2. Change the function call (Step 3)
 * 3. Make sure the API key is in .env
 * 
 * Example - Switch from OpenAI to DeepSeek:
 * 
 * Before:
 *   require_once '/.../openai.php';
 *   $response = openai_complete(...);
 * 
 * After:
 *   require_once '/.../deepseek.php';
 *   $response = deepseek_complete(...);
 * 
 * That's it! No other changes needed.
 * 
 * WHY THIS WORKS ON RASPBERRY PI:
 * ───────────────────────────────
 * You're NOT running the AI model locally!
 * 
 * Your Pi just:
 * ✅ Sends text over HTTP (curl)
 * ✅ Receives text back (JSON)
 * ✅ Parses and displays it
 * 
 * The heavy lifting (billions of matrix operations) happens
 * on remote servers with expensive GPUs. Your Pi is the messenger.
 * 
 * No ML libraries. No tensors. No pip install torch.
 * Just plain PHP, making HTTP calls, getting text back.
 * 
 * AVAILABLE CLIENTS:
 * ─────────────────
 * ✅ OpenAI    - GPT-4o-mini (fast, cheap, smart)
 * ✅ DeepSeek  - Very cheap, still capable
 * ✅ Claude    - Anthropic's models (excellent reasoning)
 * ✅ Gemini    - Google's models (multimodal support)
 * ✅ Mistral   - European alternative, fast
 * ✅ Kimi      - Moonshot AI, long context
 * 
 * Each client is a single PHP file (100-200 lines).
 * Each uses curl to make HTTP requests.
 * Each parses JSON responses.
 * 
 * That's the beauty: AI doesn't have to be complicated.
 * Understanding beats horsepower. 🚀🍕
 */
?>
