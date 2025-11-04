<?php
// api/partner_addresses.php - COMPLETE VERSION

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

ob_start();
header('Content-Type: application/json');

try {
    error_log("=== PARTNER ADDRESSES API CALLED ===");

    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/partner.php';

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
    error_log("Action: " . $action);

    if (empty($action)) {
        throw new Exception('Action is required', 400);
    }

    switch ($action) {
        case 'add_address':
            error_log("=== PROCESSING ADD ADDRESS ===");

            $partnerId = $_POST['partner_id'] ?? null;
            $addressData = [
                'address_type' => $_POST['address_type'] ?? 'billing',
                'address_line1' => $_POST['address_line1'] ?? '',
                'address_line2' => $_POST['address_line2'] ?? null,
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? null,
                'postal_code' => $_POST['postal_code'] ?? null,
                'country' => $_POST['country'] ?? 'Indonesia',
                'contact_person' => $_POST['contact_person'] ?? null,
                'phone' => $_POST['phone'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'is_primary' => isset($_POST['is_primary'])
            ];

            error_log("Partner ID: " . $partnerId);
            error_log("Address Data: " . print_r($addressData, true));

            // Validation
            if (!$partnerId) {
                throw new Exception('Partner ID is required', 400);
            }
            if (empty($addressData['address_line1'])) {
                throw new Exception('Address Line 1 is required', 400);
            }
            if (empty($addressData['city'])) {
                throw new Exception('City is required', 400);
            }

            $result = Partner::addAddress($partnerId, $addressData);

            if (!$result) {
                throw new Exception('Failed to add address', 500);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Address added successfully'
            ]);
            break;

        case 'update_address':
            error_log("=== PROCESSING UPDATE ADDRESS ===");

            $addressId = $_POST['address_id'] ?? null;
            $addressData = [
                'address_type' => $_POST['address_type'] ?? 'billing',
                'address_line1' => $_POST['address_line1'] ?? '',
                'address_line2' => $_POST['address_line2'] ?? null,
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? null,
                'postal_code' => $_POST['postal_code'] ?? null,
                'country' => $_POST['country'] ?? 'Indonesia',
                'contact_person' => $_POST['contact_person'] ?? null,
                'phone' => $_POST['phone'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'is_primary' => isset($_POST['is_primary'])
            ];

            error_log("Address ID: " . $addressId);

            if (!$addressId) {
                throw new Exception('Address ID is required', 400);
            }

            $result = Partner::updateAddress($addressId, $addressData);

            if (!$result) {
                throw new Exception('Failed to update address', 500);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Address updated successfully'
            ]);
            break;

        case 'delete_address':
            error_log("=== PROCESSING DELETE ADDRESS ===");

            $addressId = $_POST['address_id'] ?? null;
            error_log("Address ID to delete: " . $addressId);

            if (!$addressId) {
                throw new Exception('Address ID is required', 400);
            }

            $result = Partner::deleteAddress($addressId);
            error_log("Delete result: " . ($result ? 'SUCCESS' : 'FAILED'));

            if (!$result) {
                throw new Exception('Failed to delete address', 500);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid action: ' . $action, 400);
    }
} catch (Exception $e) {
    error_log("=== PARTNER ADDRESSES API ERROR ===");
    error_log("Error: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
