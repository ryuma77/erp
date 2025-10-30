<?php
// api/product_vendors.php

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering dengan level yang lebih dalam
ob_start();

// Set header JSON di awal
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/product.php';
    require_once __DIR__ . '/../includes/partner.php';

    // Clear any previous output
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

            error_log("=== ADD VENDOR API ===");
            error_log("Product ID: $productId, Partner ID: $partnerId");

            if (!$productId || !$partnerId) {
                throw new Exception('Product ID and Partner ID are required', 400);
            }

            $result = Product::addVendor($productId, $partnerId, $vendorData);

            if (!$result) {
                throw new Exception('Failed to add vendor to product', 500);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Vendor added successfully',
                'data' => [
                    'product_id' => $productId,
                    'partner_id' => $partnerId
                ]
            ]);
            break;

        case 'create_vendor_and_link':
            $productId = $_POST['product_id'] ?? null;
            $newVendorData = json_decode($_POST['new_vendor_data'] ?? '{}', true);
            $vendorData = json_decode($_POST['vendor_data'] ?? '{}', true);

            error_log("=== CREATE VENDOR AND LINK API ===");
            error_log("Product ID: $productId");

            if (!$productId) {
                throw new Exception('Product ID is required', 400);
            }

            if (empty($newVendorData['partner_name'])) {
                throw new Exception('Vendor Name is required', 400);
            }

            // Create new partner
            $partnerId = Partner::create($newVendorData);

            if (!$partnerId) {
                throw new Exception('Failed to create new vendor', 500);
            }

            error_log("New partner created with ID: $partnerId");

            // Link to product
            $result = Product::addVendor($productId, $partnerId, $vendorData);

            if (!$result) {
                // Vendor created but linking failed
                echo json_encode([
                    'success' => false,
                    'message' => 'Vendor created but failed to link to product',
                    'partner_id' => $partnerId
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'New vendor created and linked successfully',
                    'partner_id' => $partnerId
                ]);
            }
            break;

        case 'remove_vendor':
            $vendorId = $_POST['vendor_id'] ?? null;

            if (!$vendorId) {
                throw new Exception('Vendor ID is required', 400);
            }

            $result = Product::removeVendor($vendorId);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Vendor removed successfully' : 'Failed to remove vendor'
            ]);
            break;

        default:
            throw new Exception('Invalid action: ' . $action, 400);
    }
} catch (Exception $e) {
    // Clear all output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Log error
    error_log("Product Vendors API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Return JSON error
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]);
}

// Ensure no extra output
exit;
