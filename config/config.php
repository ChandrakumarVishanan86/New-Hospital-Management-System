<?php
/**
 * MediCore HMS - global settings.
 * Edit DB_* if your WAMP MySQL user/password/port differ from the defaults.
 */
declare(strict_types=1);

// ---- Database (WAMP default: root / empty password) -------------------
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');            // use 3307 if your WAMP MySQL/MariaDB listens there
define('DB_NAME', 'hospital_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- Application -------------------------------------------------------
define('APP_NAME', 'MediCore HMS');
define('APP_BASE', '/hospital_management');   // URL path of the project (no trailing slash)
define('APP_ROOT', dirname(__DIR__));
define('APP_TIMEZONE', 'Asia/Colombo');

// ---- Hospital settings -------------------------------------------------
define('CURRENCY', 'Rs.');
define('INVOICE_DUE_DAYS', 7);
define('BOOKING_DAYS_AHEAD', 30);           // how far ahead appointments can be booked
define('LOW_STOCK_WARN_DAYS', 60);          // medicine batches expiring within this many days are flagged
define('HOSPITAL_NAME', 'MediCore General Hospital');
define('HOSPITAL_ADDRESS', '100 Hospital Road, Colombo 08');
define('HOSPITAL_PHONE', '+94 11 200 3000');
define('DEFAULT_PATIENT_PASSWORD', 'patient123');

date_default_timezone_set(APP_TIMEZONE);

define('APP_DEBUG', true);                  // set false in production
ini_set('display_errors', '0');             // errors never leak into JSON responses
ini_set('log_errors', '1');
error_reporting(E_ALL);
