<?php
/**
 * Application Configuration
 */

// Define absolute path
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('API_PATH', BASE_PATH . '/api');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Function to load .env variables
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Load .env
loadEnv(BASE_PATH . '/.env');

// Environment variable helper (handles Vercel/PaaS differences)
function env($key, $default = null) {
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    $val = getenv($key);
    if ($val !== false) return $val;
    return $default;
}

// Common formatting functions
function formatINR($amount) {
    // Basic formatting for Indian Rupee
    // E.g. 1,00,000
    $amount = round($amount, 2);
    $fmt = new NumberFormatter('en_IN', NumberFormatter::CURRENCY);
    $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
    return $fmt->formatCurrency($amount, 'INR');
}
