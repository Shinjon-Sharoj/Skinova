<?php
/**
 * Copy this file to config/local.php and edit. config/local.php is loaded before
 * the defaults in config/app.php and config/database.php, so anything defined
 * here wins. Never commit config/local.php.
 */

// --- Database (XAMPP defaults shown) ---
// define('DB_HOST', '127.0.0.1');
// define('DB_NAME', 'skinova');
// define('DB_USER', 'root');
// define('DB_PASS', '');

// --- Environment ---
// define('APP_ENV', 'production');          // hides debug output
// define('APP_BASE_PATH', '/skinova');      // only if auto-detection is wrong
// define('CURRENCY_SYMBOL', '$');

// --- JWT (API tokens). If omitted a random secret is generated in storage/jwt.secret ---
// define('JWT_SECRET', 'a-long-random-string');

// --- Real AI (optional). Without it the site uses the knowledge base and says so. ---
// define('AI_PROVIDER', 'openai');          // 'openai' (OpenAI-compatible) or 'gemini'
// define('AI_API_KEY', 'sk-...');
// define('AI_BASE_URL', 'https://api.openai.com/v1');
// define('AI_MODEL', 'gpt-4o-mini');
// define('AI_VISION_MODEL', 'gpt-4o-mini');  // gemini: e.g. gemini-1.5-flash

// --- Online payment (optional). Default is the built-in sandbox (no real money). ---
// define('ONLINE_PAYMENT_PROVIDER', 'stripe');
// define('STRIPE_SECRET_KEY', 'sk_test_...');
// define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');

// --- Mail (optional) ---
// define('MAIL_MODE', 'mail');
// define('MAIL_FROM', 'no-reply@yourdomain.com');