<?php
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/partner.php';

// Initialize variables
$partner = null;
$isEdit = false;
$addresses = [];
$accountingInfo = [];
$purchaseHistory = [];
$salesHistory = [];

if (isset($_GET['id'])) {
    $partner = Partner::getById($_GET['id']);
    if ($partner) {
        $isEdit = true;
        $pageTitle = "Edit Partner - " . htmlspecialchars($partner['partner_name']);
        $addresses = Partner::getAddresses($partner['id']);
        // Load other data: accounting, purchase history, sales history
    } else {
        setFlashMessage('error', 'Partner not found!');
        header('Location: partner.php');
        exit;
    }
} else {
    $pageTitle = "Add New Partner";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'partner_code' => sanitizeInput($_POST['partner_code'] ?? ''),
        'partner_name' => sanitizeInput($_POST['partner_name'] ?? ''),
        'partner_type' => sanitizeInput($_POST['partner_type'] ?? 'vendor'),
        'contact_person' => sanitizeInput($_POST['contact_person'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'website' => sanitizeInput($_POST['website'] ?? ''),
        'tax_id' => sanitizeInput($_POST['tax_id'] ?? ''),
        'credit_limit' => (float) ($_POST['credit_limit'] ?? 0),
        'payment_terms' => sanitizeInput($_POST['payment_terms'] ?? ''),
        'currency_code' => sanitizeInput($_POST['currency_code'] ?? 'IDR'),
        'notes' => sanitizeInput($_POST['notes'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? true : false
    ];

    if ($isEdit) {
        $result = Partner::update($_POST['id'], $data);
        $message = $result ? 'Partner updated successfully!' : 'Failed to update partner!';

        if ($result) {
            setFlashMessage('success', $message);
            // Refresh partner data
            $partner = Partner::getById($_POST['id']);
        } else {
            setFlashMessage('error', $message);
            $partner = array_merge($partner ?: [], $data);
        }
    } else {
        $result = Partner::create($data);

        if ($result) {
            setFlashMessage('success', 'Partner created successfully!');
            header('Location: partner_form.php?id=' . $result);
            exit;
        } else {
            setFlashMessage('error', 'Failed to create partner!');
            $partner = $data;
        }
    }
}

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>

<main class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <h1 class="page-title"><?= $pageTitle ?></h1>
        </div>
        <div class="header-right">
            <div class="header-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="user-dropdown">
                    <button class="user-menu-btn">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentUser['name']) ?>&background=007bff&color=fff" alt="<?= htmlspecialchars($currentUser['name']) ?>">
                        <span><?= htmlspecialchars($currentUser['name']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="page-content">
        <?php displayFlashMessage(); ?>

        <!-- Quick Actions Bar -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="submit" form="partnerForm" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i><?= $isEdit ? 'Update' : 'Save' ?>
                        </button>
                        <a href="partner.php" class="btn btn-secondary btn-sm ms-1">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <?php if ($isEdit): ?>
                            <a href="partner_audit.php?id=<?= $partner['id'] ?>" class="btn btn-outline-info btn-sm ms-1">
                                <i class="fas fa-history me-1"></i>Audit Trail
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small">
                        <?php if ($isEdit): ?>
                            Last updated: <?= date('M j, Y H:i', strtotime($partner['updated_at'] ?? 'now')) ?>
                        <?php else: ?>
                            New Partner
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- LEFT COLUMN - Main Form -->
            <div class="col-md-8">
                <form method="POST" id="partnerForm">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $partner['id'] ?>">
                    <?php endif; ?>

                    <!-- Basic Information -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Basic Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Partner Code</label>
                                        <input type="text" class="form-control" name="partner_code"
                                            value="<?= htmlspecialchars($partner['partner_code'] ?? '') ?>"
                                            <?= $isEdit ? 'readonly' : '' ?>>
                                        <div class="form-text">Auto-generated if empty</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Partner Type *</label>
                                        <select class="form-select" name="partner_type" required>
                                            <option value="vendor" <?= ($partner['partner_type'] ?? 'vendor') == 'vendor' ? 'selected' : '' ?>>Vendor</option>
                                            <option value="customer" <?= ($partner['partner_type'] ?? '') == 'customer' ? 'selected' : '' ?>>Customer</option>
                                            <option value="both" <?= ($partner['partner_type'] ?? '') == 'both' ? 'selected' : '' ?>>Vendor & Customer</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Partner Name *</label>
                                <input type="text" class="form-control" name="partner_name"
                                    value="<?= htmlspecialchars($partner['partner_name'] ?? '') ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" class="form-control" name="contact_person"
                                            value="<?= htmlspecialchars($partner['contact_person'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tax ID</label>
                                        <input type="text" class="form-control" name="tax_id"
                                            value="<?= htmlspecialchars($partner['tax_id'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-address-book me-2"></i>Contact Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email"
                                            value="<?= htmlspecialchars($partner['email'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" name="phone"
                                            value="<?= htmlspecialchars($partner['phone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website"
                                            value="<?= htmlspecialchars($partner['website'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Currency</label>
                                        <select class="form-select" name="currency_code">
                                            <option value="IDR" <?= ($partner['currency_code'] ?? 'IDR') == 'IDR' ? 'selected' : '' ?>>IDR - Indonesian Rupiah</option>
                                            <option value="USD" <?= ($partner['currency_code'] ?? '') == 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                                            <option value="EUR" <?= ($partner['currency_code'] ?? '') == 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Information -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Financial Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Credit Limit</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" name="credit_limit"
                                                value="<?= $partner['credit_limit'] ?? '0' ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Terms</label>
                                        <select class="form-select" name="payment_terms">
                                            <option value="">-- Select Terms --</option>
                                            <option value="net_15" <?= ($partner['payment_terms'] ?? '') == 'net_15' ? 'selected' : '' ?>>Net 15</option>
                                            <option value="net_30" <?= ($partner['payment_terms'] ?? '') == 'net_30' ? 'selected' : '' ?>>Net 30</option>
                                            <option value="net_60" <?= ($partner['payment_terms'] ?? '') == 'net_60' ? 'selected' : '' ?>>Net 60</option>
                                            <option value="due_on_receipt" <?= ($partner['payment_terms'] ?? '') == 'due_on_receipt' ? 'selected' : '' ?>>Due on Receipt</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($partner['notes'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" role="switch"
                                        <?= ($partner['is_active'] ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label">Active Partner</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- RIGHT COLUMN - Additional Sections -->
            <div class="col-md-4">
                <!-- Quick Stats -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Quick Stats
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($isEdit): ?>
                            <div class="row text-center">
                                <div class="col-6 border-end">
                                    <div class="h6 text-primary mb-0">0</div>
                                    <small class="text-muted">Total Purchases</small>
                                </div>
                                <div class="col-6">
                                    <div class="h6 text-success mb-0">0</div>
                                    <small class="text-muted">Total Sales</small>
                                </div>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">Outstanding Balance:</small>
                                <div class="h5 text-warning">Rp 0</div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-2">
                                <small class="text-muted">Save partner to view statistics</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Additional Sections Accordion -->
                <?php if ($isEdit): ?>
                    <div class="accordion" id="partnerSections">
                        <!-- Addresses Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#addressesSection">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>Addresses
                                    <span class="badge bg-primary ms-2"><?= count($addresses) ?></span>
                                </button>
                            </h2>
                            <div id="addressesSection" class="accordion-collapse collapse" data-bs-parent="#partnerSections">
                                <div class="accordion-body p-2">
                                    <?php if (!empty($addresses)): ?>
                                        <?php foreach ($addresses as $address): ?>
                                            <div class="border-bottom pb-2 mb-2 address-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center">
                                                            <strong class="small"><?= htmlspecialchars($address['address_type'] ?? 'Address') ?></strong>
                                                            <?php if ($address['is_primary'] ?? false): ?>
                                                                <span class="badge bg-success badge-sm ms-1">Primary</span>
                                                            <?php endif; ?>
                                                            <?php if ($address['address_name']): ?>
                                                                <span class="text-muted ms-2">(<?= htmlspecialchars($address['address_name']) ?>)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="small text-muted mt-1">
                                                            <?= htmlspecialchars($address['address_line1'] ?? '') ?>
                                                            <?= $address['address_line2'] ? ', ' . htmlspecialchars($address['address_line2']) : '' ?>
                                                        </div>
                                                        <div class="small text-muted">
                                                            <?= $address['city'] ?? '' ?><?= $address['state'] ? ', ' . htmlspecialchars($address['state']) : '' ?><?= $address['postal_code'] ? ' ' . htmlspecialchars($address['postal_code']) : '' ?>
                                                        </div>
                                                        <div class="small text-muted"><?= htmlspecialchars($address['country'] ?? '') ?></div>
                                                        <?php if ($address['contact_person'] || $address['phone']): ?>
                                                            <div class="small text-muted mt-1">
                                                                <?= $address['contact_person'] ? 'Contact: ' . htmlspecialchars($address['contact_person']) : '' ?>
                                                                <?= $address['phone'] ? ' | ' . htmlspecialchars($address['phone']) : '' ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="btn-group btn-group-sm ms-2">
                                                        <button type="button" class="btn btn-outline-primary edit-address"
                                                            data-address-id="<?= $address['id'] ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger delete-address"
                                                            data-address-id="<?= $address['id'] ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-2">
                                            <small class="text-muted">No addresses added</small>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-center mt-2">
                                        <button class="btn btn-outline-primary btn-sm" id="addAddressBtn">
                                            <i class="fas fa-plus me-1"></i>Add Address
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accounting Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accountingSection">
                                    <i class="fas fa-calculator text-success me-2"></i>Accounting
                                </button>
                            </h2>
                            <div id="accountingSection" class="accordion-collapse collapse" data-bs-parent="#partnerSections">
                                <div class="accordion-body p-2">
                                    <div class="small">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Account Receivable:</span>
                                            <strong class="text-primary">Rp 0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Account Payable:</span>
                                            <strong class="text-danger">Rp 0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Credit Used:</span>
                                            <strong class="text-warning">Rp 0</strong>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <button class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-cog me-1"></i>Configure
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purchase History Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#purchaseSection">
                                    <i class="fas fa-shopping-cart text-warning me-2"></i>Purchase History
                                    <span class="badge bg-warning ms-2">0</span>
                                </button>
                            </h2>
                            <div id="purchaseSection" class="accordion-collapse collapse" data-bs-parent="#partnerSections">
                                <div class="accordion-body p-2">
                                    <div class="text-center py-2">
                                        <small class="text-muted">No purchase history</small>
                                    </div>
                                    <div class="text-center mt-2">
                                        <button class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-list me-1"></i>View All
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales History Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#salesSection">
                                    <i class="fas fa-chart-line text-info me-2"></i>Sales History
                                    <span class="badge bg-info ms-2">0</span>
                                </button>
                            </h2>
                            <div id="salesSection" class="accordion-collapse collapse" data-bs-parent="#partnerSections">
                                <div class="accordion-body p-2">
                                    <div class="text-center py-2">
                                        <small class="text-muted">No sales history</small>
                                    </div>
                                    <div class="text-center mt-2">
                                        <button class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-list me-1"></i>View All
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Address Modal -->
    <div class="modal fade" id="addressModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addressModalTitle">Add Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addressForm">
                        <input type="hidden" name="address_id" id="address_id">
                        <input type="hidden" name="partner_id" value="<?= $partner['id'] ?? '' ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Address Type *</label>
                                    <select class="form-select" name="address_type" required>
                                        <option value="billing">Billing Address</option>
                                        <option value="shipping">Shipping Address</option>
                                        <option value="office">Office Address</option>
                                        <option value="warehouse">Warehouse</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Address Name</label>
                                    <input type="text" class="form-control" name="address_name"
                                        placeholder="e.g., Head Office, Main Warehouse">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address Line 1 *</label>
                            <input type="text" class="form-control" name="address_line1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" name="address_line2">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">City *</label>
                                    <input type="text" class="form-control" name="city" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">State/Province</label>
                                    <input type="text" class="form-control" name="state">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" name="postal_code">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Country *</label>
                            <select class="form-select" name="country" required>
                                <option value="Indonesia">Indonesia</option>
                                <option value="Malaysia">Malaysia</option>
                                <option value="Singapore">Singapore</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" name="contact_person">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_primary" id="address_is_primary">
                            <label class="form-check-label" for="address_is_primary">Set as Primary Address</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteAddressBtn" style="display: none;">Delete</button>
                    <button type="button" class="btn btn-primary" id="saveAddressBtn">Save Address</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Address Management JavaScript - FIXED VERSION
    document.addEventListener('DOMContentLoaded', function() {
        initializeAddressManagement();
    });

    function initializeAddressManagement() {
        const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
        const saveAddressBtn = document.getElementById('saveAddressBtn');
        const deleteAddressBtn = document.getElementById('deleteAddressBtn');
        const addAddressBtn = document.getElementById('addAddressBtn');

        // Add Address Button
        if (addAddressBtn) {
            addAddressBtn.addEventListener('click', function() {
                resetAddressForm();
                document.getElementById('addressModalTitle').textContent = 'Add Address';
                deleteAddressBtn.style.display = 'none';
                addressModal.show();
            });
        }

        // Setup event listeners untuk edit dan delete buttons
        setupAddressEventListeners();

        // Save Address Button dengan loading state
        if (saveAddressBtn) {
            saveAddressBtn.addEventListener('click', function() {
                // Prevent multiple clicks
                if (this.disabled) return;

                setButtonLoading(this, true);
                saveAddress();
            });
        }

        // Delete Address Button in Modal
        if (deleteAddressBtn) {
            deleteAddressBtn.addEventListener('click', function() {
                const addressId = document.getElementById('address_id').value;
                if (addressId && confirm('Are you sure you want to delete this address?')) {
                    setButtonLoading(this, true);
                    deleteAddress(addressId);
                }
            });
        }
    }

    function setupAddressEventListeners() {
        // Edit Address Buttons - gunakan event delegation
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-address')) {
                const button = e.target.closest('.edit-address');
                const addressId = button.getAttribute('data-address-id');
                loadAddressData(addressId);
            }

            if (e.target.closest('.delete-address')) {
                const button = e.target.closest('.delete-address');
                const addressId = button.getAttribute('data-address-id');
                if (confirm('Are you sure you want to delete this address?')) {
                    setButtonLoading(button, true);
                    deleteAddress(addressId);
                }
            }
        });
    }

    function setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            const originalText = button.innerHTML;
            button.setAttribute('data-original-text', originalText);
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
        } else {
            button.disabled = false;
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.innerHTML = originalText;
            }
        }
    }

    function resetAddressForm() {
        document.getElementById('addressForm').reset();
        document.getElementById('address_id').value = '';
        document.querySelector('select[name="country"]').value = 'Indonesia';
        document.querySelector('input[name="is_primary"]').checked = false;

        // Reset button states
        const saveBtn = document.getElementById('saveAddressBtn');
        const deleteBtn = document.getElementById('deleteAddressBtn');
        if (saveBtn) setButtonLoading(saveBtn, false);
        if (deleteBtn) setButtonLoading(deleteBtn, false);
    }

    function loadAddressData(addressId) {
        // Untuk phase 1, kita implement simple version
        // Phase 2 bisa implement AJAX load data
        const addressItem = document.querySelector(`[data-address-id="${addressId}"]`).closest('.address-item');

        // Extract basic info dari DOM
        const addressType = addressItem.querySelector('strong').textContent.trim();
        const addressLines = addressItem.querySelectorAll('.text-muted');
        const addressLine1 = addressLines[0].textContent.split(',')[0].trim();

        // Populate form (basic fields saja dulu)
        document.getElementById('addressModalTitle').textContent = 'Edit Address';
        document.getElementById('address_id').value = addressId;
        document.querySelector('select[name="address_type"]').value = addressType.toLowerCase();
        document.querySelector('input[name="address_line1"]').value = addressLine1;

        // Show delete button
        document.getElementById('deleteAddressBtn').style.display = 'block';

        // Show modal
        new bootstrap.Modal(document.getElementById('addressModal')).show();
    }

    function saveAddress() {
        const formData = new FormData(document.getElementById('addressForm'));
        const isEdit = !!document.getElementById('address_id').value;
        formData.append('action', isEdit ? 'update_address' : 'add_address');

        console.log('Saving address...', Object.fromEntries(formData));

        fetch('/erp/api/partner_addresses.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Save response:', data);

                // Reset button loading state
                setButtonLoading(document.getElementById('saveAddressBtn'), false);

                if (data.success) {
                    showAlert(data.message, 'success');

                    // Hide modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addressModal'));
                    if (modal) modal.hide();

                    // ⬇️ REFRESH LANGSUNG - HAPUS TIMEOUT
                    console.log('Refreshing page immediately...');
                    location.reload();

                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                setButtonLoading(document.getElementById('saveAddressBtn'), false);
                showAlert('Error saving address: ' + error.message, 'error');
            });
    }

    function deleteAddress(addressId) {
        console.log('Deleting address:', addressId);

        const formData = new FormData();
        formData.append('action', 'delete_address');
        formData.append('address_id', addressId);

        fetch('/erp/api/partner_addresses.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Delete response:', data);

                // Reset button loading states
                document.querySelectorAll('.delete-address').forEach(btn => {
                    setButtonLoading(btn, false);
                });
                setButtonLoading(document.getElementById('deleteAddressBtn'), false);

                if (data.success) {
                    showAlert(data.message, 'success');

                    // ⬇️ REFRESH LANGSUNG - HAPUS TIMEOUT
                    console.log('Refreshing page immediately after delete...');
                    location.reload();

                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                document.querySelectorAll('.delete-address').forEach(btn => {
                    setButtonLoading(btn, false);
                });
                setButtonLoading(document.getElementById('deleteAddressBtn'), false);
                showAlert('Error deleting address: ' + error.message, 'error');
            });
    }

    // Improved alert function
    function showAlert(message, type = 'info') {
        // Remove existing alerts
        document.querySelectorAll('.custom-alert').forEach(alert => alert.remove());

        const alertDiv = document.createElement('div');
        alertDiv.className = `custom-alert alert alert-${type} alert-dismissible fade show`;
        alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

        document.body.appendChild(alertDiv);

        // Auto remove setelah 5 detik
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
</script>

<?php
ob_end_flush();
require_once __DIR__ . '/../../templates/footer.php';
?>