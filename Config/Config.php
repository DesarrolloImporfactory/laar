<?php
// Load environment variables
require_once __DIR__ . '/App/DotEnv.php';

// Load .env file
$dotenv = new DotEnv(__DIR__ . '/../.env');
$dotenv->load();

// Helper function to get environment variables with default values
function env($key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    // Convert string booleans to actual booleans
    if (strtolower($value) === 'true') {
        return true;
    }
    if (strtolower($value) === 'false') {
        return false;
    }

    return $value;
}

// Database Configuration - Primary
define('HOST', env('DB_HOST', 'localhost'));
define('USER', env('DB_USER', 'root'));
define('PASSWORD', env('DB_PASSWORD', ''));
define('DB', env('DB_NAME', 'database'));
define('CHARSET', env('DB_CHARSET', 'utf8'));

// Database Configuration - Secondary
define('HOST2', env('DB_HOST2', 'localhost'));
define('USER2', env('DB_USER2', 'root'));
define('PASSWORD2', env('DB_PASSWORD2', ''));
define('DB2', env('DB_NAME2', 'database'));
define('CHARSET2', env('DB_CHARSET2', 'utf8'));

// Application Configuration
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));
