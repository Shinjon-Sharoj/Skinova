<?php
/**
 * Application configuration.
 *
 * Secrets (DB password, JWT secret, AI keys, payment keys, SMTP) belong in
 * config/local.php which is NOT committed. See config/local.example.php.
 */

if (defined('SKINOVA_APP_CONFIG_LOADED')) {
    return;
}
define('SKINOVA_APP_CONFIG_LOADED', true);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(_DIR_));
}

// Local overrides first so every if (!defined()) below can be overridden.
if (is_file(_DIR_ . '/local.php')) {
    require _DIR_ . '/local.php';
}

function _cfg(string $name, $default): void
{
    if (!defined($name)) {
        define($name, $default);
    }
}

// --- General ---------------------------------------------------------------
_cfg('APP_NAME', 'Skinova');
_cfg('APP_TAGLINE', 'Smart skincare, shopping and expert consultation');
_cfg('APP_ENV', 'development');             // development | production
_cfg('APP_DEBUG', APP_ENV !== 'production');
_cfg('APP_TIMEZONE', 'Asia/Dhaka');
_cfg('APP_BASE_PATH', null);                // null = auto-detect, or e.g. '/skinova'
_cfg('CURRENCY_SYMBOL', '$');
_cfg('CURRENCY_CODE', 'USD');
_cfg('DEFAULT_COUNTRY', 'United States');
_cfg('SUPPORT_EMAIL', 'support@skinova.local');
_cfg('SUPPORT_PHONE', '+1 555 010 0199');

// --- Sessions / security ---------------------------------------------------
_cfg('SESSION_NAME', 'SKINOVA_SID');
_cfg('SESSION_IDLE_TIMEOUT', 7200);         // seconds
_cfg('PASSWORD_MIN_LENGTH', 8);
_cfg('JWT_TTL', 3600);                      // API token lifetime (seconds)
_cfg('JWT_ISSUER', 'skinova');
_cfg('VERIFICATION_TOKEN_TTL', 86400);      // email link / OTP validity
_cfg('PASSWORD_RESET_TTL', 3600);

// --- Mail / SMS -------------------------------------------------------------
// 'log'  : write messages to storage/logs/mail.log (safe default for XAMPP)
// 'mail' : use PHP mail() (needs sendmail/SMTP configured in php.ini)
_cfg('MAIL_MODE', 'log');
_cfg('MAIL_FROM', 'no-reply@skinova.local');
_cfg('MAIL_FROM_NAME', APP_NAME);
// 'log' only for now. Plug an SMS gateway into includes/functions.php::send_sms().
_cfg('SMS_MODE', 'log');
// When true (and APP_DEBUG) verification links/codes are shown on-screen so the
// project can be tested without an SMTP/SMS service.
_cfg('SHOW_DEV_VERIFICATION', APP_DEBUG);

// --- AI integration ---------------------------------------------------------
// AI_PROVIDER: 'none' (default - the site then uses the knowledge base / rule
// engine and clearly labels answers as such), 'openai' (any OpenAI-compatible
// API), or 'gemini'.
_cfg('AI_PROVIDER', 'none');
_cfg('AI_API_KEY', '');
_cfg('AI_BASE_URL', 'https://api.openai.com/v1');
_cfg('AI_MODEL', 'gpt-4o-mini');
_cfg('AI_VISION_MODEL', 'gpt-4o-mini');
_cfg('AI_TIMEOUT', 30);

// --- Payments ---------------------------------------------------------------
// ONLINE_PAYMENT_PROVIDER: 'sandbox' (built-in test gateway, no real charge) or 'stripe'
_cfg('ONLINE_PAYMENT_PROVIDER', 'sandbox');
_cfg('STRIPE_SECRET_KEY', '');
_cfg('STRIPE_PUBLISHABLE_KEY', '');

// --- Uploads ----------------------------------------------------------------
_cfg('UPLOAD_DIR', APP_ROOT . '/assets/uploads');
_cfg('PRIVATE_UPLOAD_SUBDIRS', ['prescriptions', 'skin-images']);

// --- Consultation chat -------------------------------------------------------
_cfg('CHAT_POLL_INTERVAL_MS', 3000);

date_default_timezone_set(APP_TIMEZONE);
