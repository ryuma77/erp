<?php
// Start session
session_start();

// Base URL configuration
define('BASE_URL', 'http://localhost/erp');

// Application settings
define('APP_NAME', 'ERP System');
define('APP_VERSION', '1.0.0');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'erp');
define('DB_USER', 'adempiere');
define('DB_PASS', 'IDempiere100%');

// Timezone setting
date_default_timezone_set('Asia/Jakarta');
