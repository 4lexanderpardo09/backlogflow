<?php
/**
 * Application configuration.
 * Values are read from environment variables, loaded from a project-root
 * .env file (see .env.example) if present. Real environment variables
 * always take precedence over .env, so this is safe to use in any hosting
 * setup that already injects its own env vars.
 */

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

// --- Database connection -----------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'backlogflow');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// --- Application -----------------------------------------------------------
define('APP_NAME', 'BacklogFlow');
define('APP_TIMEZONE', 'America/Bogota');

// --- Paths -------------------------------------------------------------
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/Views');

date_default_timezone_set(APP_TIMEZONE);
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === '1' ? '1' : '0');
