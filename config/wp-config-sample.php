<?php
/**
 * Konfiguracja WordPress - Sklep ze złotem
 * 
 * Wymagania:
 * - PHP 8.1+
 * - MySQL 8.0+ / MariaDB 10.6+
 * - HTTPS (obowiązkowo dla WooCommerce)
 */

define('DB_NAME',     'gold_shop');
define('DB_USER',     'gold_shop_user');
define('DB_PASSWORD', 'twoje_haslo_bazy');
define('DB_HOST',     'localhost');
define('DB_CHARSET',  'utf8mb4');
define('DB_COLLATE',  'utf8mb4_polish_ci');

$table_prefix = 'gs_';

/* Klucze bezpieczeństwa - wygeneruj na https://api.wordpress.org/secret-key/1.1/salt/ */
define('AUTH_KEY',         'wpisz_wygenerowany_klucz');
define('SECURE_AUTH_KEY',  'wpisz_wygenerowany_klucz');
define('LOGGED_IN_KEY',    'wpisz_wygenerowany_klucz');
define('NONCE_KEY',        'wpisz_wygenerowany_klucz');
define('AUTH_SALT',        'wpisz_wygenerowany_klucz');
define('SECURE_AUTH_SALT', 'wpisz_wygenerowany_klucz');
define('LOGGED_IN_SALT',   'wpisz_wygenerowany_klucz');
define('NONCE_SALT',       'wpisz_wygenerowany_klucz');

/* Debug */
define('WP_DEBUG',          false);
define('WP_DEBUG_LOG',      false);
define('WP_DEBUG_DISPLAY',  false);

/* Dysk twardy - ścieżki */
define('WP_HOME',    'https://twojadomena.pl');
define('WP_SITEURL', 'https://twojadomena.pl/wp');
define('WP_CONTENT_DIR', dirname(__FILE__) . '/wp-content');
define('WP_CONTENT_URL', WP_HOME . '/wp-content');

/* Wydajność */
define('WP_CACHE',            true);
define('WP_POST_REVISIONS',   5);
define('MEDIA_TRASH',         true);
define('EMPTY_TRASH_DAYS',    30);
define('WP_CRON_LOCK_TIMEOUT', 120);
define('DISABLE_WP_CRON',     false);
define('WP_MEMORY_LIMIT',     '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
define('WP_AUTO_UPDATE_CORE', 'minor');

/* Bezpieczeństwo */
define('FORCE_SSL_ADMIN',     true);
define('DISALLOW_FILE_EDIT',  true);
define('DISALLOW_FILE_MODS',  false);

/* WooCommerce */
define('WC_HIDE_DEPRECATED',       true);
define('WC_STORE_API',             true);

/* Multisite - nie używamy */
define('WP_ALLOW_MULTISITE', false);

/* Cache stałe dla LiteSpeed */
define('LITESPEED_ON', true);
define('LSCACHE_ADVANCED_CACHE', true);

/* WordPress */
define('AUTOMATIC_UPDATER_DISABLED', false);
define('WP_HTTP_BLOCK_EXTERNAL',     false);

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}
require_once ABSPATH . 'wp-settings.php';
