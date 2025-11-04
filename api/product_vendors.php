<?php
// api/product_vendors.php - FIXED VERSION

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

ob_start();
header('Content-Type: application/json');

try {
    // Load semua file required DI AWAL
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/partner.php'; // ⬅️ PINDAH KE SINI
    require_once __DIR__ . '/../includes/product.php';  // ⬅️ SETELAH partner.php

    // Clear output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Check authentication
    if (!Auth::isLoggedIn()) {
        throw new Exception('Unauthorized', 401);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    $action = $_POST['action'] ?? '';

    if (empty($action)) {
        throw new Exception('Action is required', 400);
    }

    switch ($action) {
        case 'add_vendor':
            $productId = $_POST['product_id'] ?? null;
            $partnerId = $_POST['partner_id'] ?? null;
            $vendorData = json_decode($_POST['vendor_data'] ?? '{}', true);

            error_log("API: add_vendor - product:$productId, partner:$partnerId");

            if (!$productId || !$partnerId) {
                throw new Exception('Product ID and Partner ID are required', 400);
            }

            $result = Product::addVendor($productId, $partnerId, $vendorData);

            if (!$result) {
                throw new Exception('Failed to add vendor to product', 500);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Vendor added successfully'
            ]);
            break;

            // ... other cases ...
    }
} catch (Exception $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    error_log("API Error: " . $e->getMessage());

    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
