<?php
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/product.php';
require_once __DIR__ . '/../../includes/unit.php';


// Initialize variables
$product = null;
$isEdit = false;
$newProductId = null;
$categories = Product::getCategories();
$units = UnitOfMeasure::getAll();

if (isset($_GET['id'])) {
    $product = Product::getById($_GET['id']);
    if ($product) {
        $isEdit = true;
        $pageTitle = "Edit Product - " . htmlspecialchars($product['product_name']);
        $productVendors = Product::getProductVendors($product['id']);
        $transactions = Product::getProductTransactions($product['id']);
        $accountingInfo = Product::getAccountingInfo($product['id']);
    } else {
        setFlashMessage('error', 'Product not found!');
        header('Location: products.php');
        exit;
    }
} else {
    $pageTitle = "Add New Product";
    $productVendors = $transactions = $accountingInfo = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'product_code' => sanitizeInput($_POST['product_code'] ?? ''),
        'product_name' => sanitizeInput($_POST['product_name'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
        'unit_price' => (float) ($_POST['unit_price'] ?? 0),
        'cost_price' => (float) ($_POST['cost_price'] ?? 0),
        'unit_id' => !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null,
        'stock_quantity' => (int) ($_POST['stock_quantity'] ?? 0),
        'min_stock_level' => (int) ($_POST['min_stock_level'] ?? 0),
        'max_stock_level' => !empty($_POST['max_stock_level']) ? (int) $_POST['max_stock_level'] : null,
        'barcode' => sanitizeInput($_POST['barcode'] ?? ''),
        'sku' => sanitizeInput($_POST['sku'] ?? ''),
        'weight' => !empty($_POST['weight']) ? (float) $_POST['weight'] : null,
        'dimensions' => sanitizeInput($_POST['dimensions'] ?? ''),
        'supplier_id' => !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null,
        'is_active' => isset($_POST['is_active']) ? true : false
    ];

    if ($isEdit) {
        $result = Product::update($_POST['id'], $data);
        $message = $result ? 'Product updated successfully!' : 'Failed to update product!';

        if ($result) {
            setFlashMessage('success', $message);
            // Refresh product data
            $product = Product::getById($_POST['id']);
        } else {
            setFlashMessage('error', $message . ' Please check the form data and try again.');
            $product = array_merge($product ?: [], $data);
        }
    } else {
        $result = Product::create($data);

        if ($result) {
            // BERHASIL CREATE - TIDAK REDIRECT, TETAP DI FORM
            $newProductId = $result; // Simpan ID product baru
            $message = 'Product created successfully!';
            setFlashMessage('success', $message);

            // Set $isEdit menjadi true agar additional sections muncul
            $isEdit = true;

            // Load product data yang baru dibuat
            $product = Product::getById($newProductId);
            $pageTitle = "Edit Product - " . htmlspecialchars($product['product_name']);

            // Load additional data untuk product baru
            $productVendors = Product::getProductVendors($newProductId);
            $transactions = Product::getProductTransactions($newProductId);
            $accountingInfo = Product::getAccountingInfo($newProductId);
        } else {
            $message = 'Failed to create product!';
            setFlashMessage('error', $message . ' Please check the form data and try again.');
            $product = $data; // Simpan data yang diinput untuk ditampilkan kembali
        }
    }
}

$pageTitle = $pageTitle ?? "Product Form";
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
                        <button type="submit" form="productForm" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i><?= $isEdit ? 'Update' : 'Save' ?>
                        </button>
                        <a href="products.php" class="btn btn-secondary btn-sm ms-1">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <?php if ($isEdit): ?>
                            <a href="product_audit.php?id=<?= $product['id'] ?>" class="btn btn-outline-info btn-sm ms-1">
                                <i class="fas fa-history me-1"></i>Audit Trail
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small">
                        <?php if ($isEdit): ?>
                            Last updated: <?= date('M j, Y H:i', strtotime($product['updated_at'])) ?>
                        <?php else: ?>
                            New Product
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- LEFT COLUMN - Main Form -->
            <div class="col-md-8">
                <form method="POST" id="productForm">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <?php endif; ?>

                    <!-- Product Header -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cube me-2"></i>Product Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Code *</label>
                                        <input type="text" class="form-control" name="product_code"
                                            value="<?= htmlspecialchars($product['product_code'] ?? '') ?>"
                                            required <?= $isEdit ? 'readonly' : '' ?>>
                                        <div class="form-text">Auto-generated if empty</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Name *</label>
                                        <input type="text" class="form-control" name="product_name"
                                            value="<?= htmlspecialchars($product['product_name'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="category_id">
                                            <option value="">-- Select Category --</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>"
                                                    <?= ($product['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['category_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Unit of Measure *</label>
                                        <select class="form-select" name="unit_id" required>
                                            <option value="">-- Select Unit --</option>
                                            <?php foreach ($units as $unit): ?>
                                                <option value="<?= $unit['id'] ?>"
                                                    <?= ($product['unit_id'] ?? '') == $unit['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($unit['unit_code']) ?> - <?= htmlspecialchars($unit['unit_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Inventory -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-money-bill-wave me-2"></i>Pricing & Inventory
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Unit Price *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" name="unit_price"
                                                value="<?= $product['unit_price'] ?? '0' ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cost Price *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" name="cost_price"
                                                value="<?= $product['cost_price'] ?? '0' ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Stock Quantity *</label>
                                        <input type="number" class="form-control" name="stock_quantity"
                                            value="<?= $product['stock_quantity'] ?? '0' ?>" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Min Stock Level</label>
                                        <input type="number" class="form-control" name="min_stock_level"
                                            value="<?= $product['min_stock_level'] ?? '0' ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Max Stock Level</label>
                                        <input type="number" class="form-control" name="max_stock_level"
                                            value="<?= $product['max_stock_level'] ?? '' ?>" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Additional Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Barcode</label>
                                        <input type="text" class="form-control" name="barcode"
                                            value="<?= htmlspecialchars($product['barcode'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SKU</label>
                                        <input type="text" class="form-control" name="sku"
                                            value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" name="weight"
                                            value="<?= $product['weight'] ?? '' ?>" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Dimensions</label>
                                        <input type="text" class="form-control" name="dimensions"
                                            value="<?= htmlspecialchars($product['dimensions'] ?? '') ?>"
                                            placeholder="L x W x H">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Supplier ID</label>
                                        <input type="number" class="form-control" name="supplier_id"
                                            value="<?= $product['supplier_id'] ?? '' ?>" min="0">
                                        <div class="form-text">Optional - for future implementation</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_active"
                                                <?= ($product['is_active'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Active Product</label>
                                        </div>
                                    </div>
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
                        <?php
                        $stock_qty = $product['stock_quantity'] ?? 0;
                        $max_stock = $product['max_stock_level'] ?? 100;
                        $stock_percentage = $max_stock > 0 ? min(100, ($stock_qty / $max_stock) * 100) : 0;
                        $progress_class = $stock_percentage < 30 ? 'bg-danger' : ($stock_percentage < 70 ? 'bg-warning' : 'bg-success');
                        ?>
                        <div class="mb-3">
                            <small class="text-muted">Stock Level</small>
                            <div class="progress mb-1" style="height: 6px;">
                                <div class="progress-bar <?= $progress_class ?>" style="width: <?= $stock_percentage ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span><?= $stock_qty ?> units</span>
                                <span><?= $max_stock ?: '∞' ?> max</span>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <div class="h6 text-primary mb-0">Rp <?= number_format($product['unit_price'] ?? 0, 0) ?></div>
                                <small class="text-muted">Sell Price</small>
                            </div>
                            <div class="col-6">
                                <div class="h6 text-success mb-0">Rp <?= number_format($product['cost_price'] ?? 0, 0) ?></div>
                                <small class="text-muted">Cost Price</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Sections Accordion -->
                <?php if ($isEdit): ?>
                    <div class="accordion" id="productSections">
                        <!-- Vendors Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#vendorsSection">
                                    <i class="fas fa-truck text-warning me-2"></i>Vendors
                                    <span class="badge bg-warning ms-2"><?= count($productVendors) ?></span>
                                </button>
                            </h2>
                            <div id="vendorsSection" class="accordion-collapse collapse" data-bs-parent="#productSections">
                                <div class="accordion-body p-2">
                                    <?php if (!empty($productVendors)): ?>
                                        <?php foreach (array_slice($productVendors, 0, 3) as $vendor): ?>
                                            <div class="border-bottom pb-2 mb-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong class="small"><?= htmlspecialchars($vendor['partner_name']) ?></strong>
                                                        <?php if ($vendor['is_primary']): ?>
                                                            <span class="badge bg-success badge-sm ms-1">P</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="small text-success">Rp <?= number_format($vendor['cost_price'], 0) ?></div>
                                                        <div class="small text-muted"><?= $vendor['lead_time_days'] ?> days</div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($productVendors) > 3): ?>
                                            <div class="text-center mt-2">
                                                <small class="text-muted">+<?= count($productVendors) - 3 ?> more vendors</small>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-center py-2">
                                            <small class="text-muted">No vendors added</small>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-center mt-2">
                                        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#vendorModal">
                                            <i class="fas fa-plus me-1"></i>Manage Vendors
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accounting Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accountingSection">
                                    <i class="fas fa-chart-line text-success me-2"></i>Accounting
                                </button>
                            </h2>
                            <div id="accountingSection" class="accordion-collapse collapse" data-bs-parent="#productSections">
                                <div class="accordion-body p-2">
                                    <div class="small">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Inventory Value:</span>
                                            <strong class="text-primary">Rp <?= number_format($accountingInfo['total_inventory_value'] ?? 0, 0) ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Total COGS:</span>
                                            <strong class="text-danger">Rp <?= number_format($accountingInfo['total_cogs'] ?? 0, 0) ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Revenue:</span>
                                            <strong class="text-success">Rp <?= number_format($accountingInfo['total_revenue'] ?? 0, 0) ?></strong>
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

                        <!-- Transactions Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#transactionsSection">
                                    <i class="fas fa-exchange-alt text-info me-2"></i>Transactions
                                    <span class="badge bg-info ms-2"><?= count($transactions) ?></span>
                                </button>
                            </h2>
                            <div id="transactionsSection" class="accordion-collapse collapse" data-bs-parent="#productSections">
                                <div class="accordion-body p-2">
                                    <?php if (!empty($transactions)): ?>
                                        <?php foreach (array_slice($transactions, 0, 3) as $transaction): ?>
                                            <div class="border-bottom pb-2 mb-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span class="badge bg-<?= $transaction['transaction_type'] == 'purchase' ? 'success' : 'danger' ?> badge-sm">
                                                            <?= substr(ucfirst($transaction['transaction_type']), 0, 1) ?>
                                                        </span>
                                                        <small class="ms-1"><?= date('d/m', strtotime($transaction['transaction_date'])) ?></small>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="small <?= $transaction['quantity'] < 0 ? 'text-danger' : 'text-success' ?>">
                                                            <?= $transaction['quantity'] > 0 ? '+' : '' ?><?= $transaction['quantity'] ?>
                                                        </div>
                                                        <div class="small text-muted">Rp <?= number_format($transaction['total_amount'], 0) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($transactions) > 3): ?>
                                            <div class="text-center mt-2">
                                                <small class="text-muted">+<?= count($transactions) - 3 ?> more transactions</small>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-center py-2">
                                            <small class="text-muted">No transactions</small>
                                        </div>
                                    <?php endif; ?>
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
</main>

<!-- Vendor Modal -->
<div class="modal fade" id="vendorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vendorModalTitle">Add Vendor to Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-3" id="vendorTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="select-vendor-tab" data-bs-toggle="tab"
                            data-bs-target="#select-vendor" type="button" role="tab">
                            <i class="fas fa-list me-1"></i>Select Existing Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="new-vendor-tab" data-bs-toggle="tab"
                            data-bs-target="#new-vendor" type="button" role="tab">
                            <i class="fas fa-plus me-1"></i>Create New Vendor
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="vendorTabContent">
                    <!-- Select Existing Vendor Tab -->
                    <div class="tab-pane fade show active" id="select-vendor" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label">Select Vendor</label>
                            <select class="form-select" id="existing_vendor_id" name="existing_vendor_id">
                                <option value="">-- Select Vendor --</option>
                                <?php
                                require_once __DIR__ . '/../../includes/partner.php';
                                $vendors = Partner::getVendors();
                                foreach ($vendors as $vendor):
                                ?>
                                    <option value="<?= $vendor['id'] ?>">
                                        <?= htmlspecialchars($vendor['partner_code']) ?> - <?= htmlspecialchars($vendor['partner_name']) ?>
                                        <?= $vendor['contact_person'] ? ' (' . htmlspecialchars($vendor['contact_person']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Create New Vendor Tab -->
                    <div class="tab-pane fade" id="new-vendor" role="tabpanel">
                        <form id="newVendorForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Vendor Code</label>
                                        <input type="text" class="form-control" name="partner_code"
                                            placeholder="Auto-generated if empty">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Partner Type</label>
                                        <select class="form-select" name="partner_type" required>
                                            <option value="vendor">Vendor Only</option>
                                            <option value="both">Vendor & Customer</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Vendor Name *</label>
                                <input type="text" class="form-control" name="partner_name" required>
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
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Vendor Pricing Information (Common for both tabs) -->
                <div class="border-top pt-3 mt-3">
                    <h6 class="mb-3"><i class="fas fa-money-bill-wave me-2"></i>Pricing & Terms</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Cost Price</label>
                                <input type="number" class="form-control" name="cost_price" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Lead Time (Days)</label>
                                <input type="number" class="form-control" name="lead_time_days" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">MOQ</label>
                                <input type="number" class="form-control" name="moq" min="1" value="1">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="vendor_notes" rows="2" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary">
                        <label class="form-check-label" for="is_primary">Set as Primary Vendor</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveVendorBtn">Save Vendor</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - initializing vendor functionality');

        // Form validation untuk product form
        const productForm = document.getElementById('productForm');
        if (productForm) {
            productForm.addEventListener('submit', function(e) {
                const unitPrice = parseFloat(document.querySelector('input[name="unit_price"]').value);
                const costPrice = parseFloat(document.querySelector('input[name="cost_price"]').value);
                if (costPrice > unitPrice) {
                    e.preventDefault();
                    alert('Warning: Cost price is higher than unit price. This may result in negative profit.');
                }
            });
        }

        // Vendor modal functionality
        <?php if ($isEdit): ?>
            console.log('Edit mode detected - initializing vendor modal');

            const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'));

            // Open vendor modal
            const addVendorBtn = document.getElementById('addVendorBtn');
            const addVendorBtn2 = document.getElementById('addVendorBtn2');

            if (addVendorBtn) {
                addVendorBtn.addEventListener('click', () => {
                    console.log('Opening vendor modal from button 1');
                    vendorModal.show();
                });
            }

            if (addVendorBtn2) {
                addVendorBtn2.addEventListener('click', () => {
                    console.log('Opening vendor modal from button 2');
                    vendorModal.show();
                });
            }

            // Save vendor
            const saveVendorBtn = document.getElementById('saveVendorBtn');
            if (saveVendorBtn) {
                saveVendorBtn.addEventListener('click', function() {
                    console.log('Save vendor button clicked');
                    saveProductVendor();
                });
            } else {
                console.error('Save vendor button not found!');
            }

            // Switch between existing and new vendor tabs
            const vendorTabs = document.querySelectorAll('#vendorTab button');
            console.log('Found vendor tabs:', vendorTabs.length);

            vendorTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    console.log('Switching to tab:', this.id);
                    // Reset forms when switching tabs
                    if (this.id === 'select-vendor-tab') {
                        document.getElementById('newVendorForm').reset();
                    } else {
                        document.getElementById('existing_vendor_id').value = '';
                    }
                });
            });

            // Delete vendor buttons
            const deleteButtons = document.querySelectorAll('.delete-vendor');
            console.log('Found delete buttons:', deleteButtons.length);

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const vendorId = this.getAttribute('data-vendor-id');
                    const vendorName = this.closest('tr').querySelector('strong').textContent;
                    console.log('Delete vendor clicked:', vendorId, vendorName);
                    if (confirm(`Are you sure you want to remove vendor "${vendorName}" from this product?`)) {
                        deleteProductVendor(vendorId);
                    }
                });
            });

            // Edit vendor buttons
            const editButtons = document.querySelectorAll('.edit-vendor');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const vendorId = this.getAttribute('data-vendor-id');
                    alert('Edit vendor feature will be implemented soon. Vendor ID: ' + vendorId);
                });
            });
        <?php endif; ?>
    });

    <?php if ($isEdit): ?>
        // Helper functions
        function showAlert(message, type = 'info') {
            console.log('Showing alert:', type, message);

            // Buat alert element
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

            // Tambahkan alert di atas page content
            const pageContent = document.querySelector('.page-content');
            if (pageContent) {
                pageContent.insertBefore(alertDiv, pageContent.firstChild);

                // Auto remove setelah 5 detik
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        }

        function resetVendorModal() {
            console.log('Resetting vendor modal');
            // Reset semua form di modal
            document.getElementById('existing_vendor_id').value = '';
            document.getElementById('newVendorForm').reset();
            document.querySelector('input[name="cost_price"]').value = '';
            document.querySelector('input[name="lead_time_days"]').value = '0';
            document.querySelector('input[name="moq"]').value = '1';
            document.querySelector('textarea[name="vendor_notes"]').value = '';
            document.querySelector('input[name="is_primary"]').checked = false;

            // Kembali ke tab pertama
            const firstTab = document.getElementById('select-vendor-tab');
            if (firstTab) {
                firstTab.click();
            }
        }

        function saveProductVendor() {
            console.log('saveProductVendor called');

            const activeTab = document.querySelector('#vendorTab .nav-link.active');
            if (!activeTab) {
                showAlert('No active tab found', 'error');
                return;
            }

            const activeTabId = activeTab.id;
            const productId = <?= $product['id'] ?? 'null' ?>;

            console.log('Active tab:', activeTabId, 'Product ID:', productId);

            if (!productId) {
                showAlert('Product ID not found', 'error');
                return;
            }

            // Collect vendor pricing data - PASTIKAN SEMUA FIELD ADA
            const vendorData = {
                lead_time_days: parseInt(document.querySelector('input[name="lead_time_days"]').value) || 0,
                cost_price: parseFloat(document.querySelector('input[name="cost_price"]').value) || 0,
                moq: parseInt(document.querySelector('input[name="moq"]').value) || 1,
                notes: document.querySelector('textarea[name="vendor_notes"]').value || '',
                is_primary: document.querySelector('input[name="is_primary"]').checked
            };

            console.log('Vendor data collected:', vendorData);

            let partnerId;

            if (activeTabId === 'select-vendor-tab') {
                // Existing vendor - PASTIKAN ELEMENTNYA ADA
                const vendorSelect = document.getElementById('existing_vendor_id');
                if (!vendorSelect) {
                    showAlert('Vendor selection element not found', 'error');
                    return;
                }

                partnerId = vendorSelect.value;
                console.log('Existing vendor selected - Partner ID:', partnerId);

                if (!partnerId) {
                    showAlert('Please select a vendor from the list', 'error');
                    return;
                }

                // Validasi tambahan - pastikan bukan placeholder
                if (partnerId === "" || partnerId === "0") {
                    showAlert('Please select a valid vendor', 'error');
                    return;
                }

                saveVendorToProduct(productId, partnerId, vendorData);
            } else {
                showAlert('XXX', 'error');
            }
        }

        function saveVendorToProduct(productId, partnerId, vendorData) {
            console.log('Saving vendor to product:', {
                productId,
                partnerId,
                vendorData
            });

            // Show loading state
            const saveBtn = document.getElementById('saveVendorBtn');
            if (!saveBtn) {
                showAlert('Save button not found', 'error');
                return;
            }

            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            saveBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'add_vendor');
            formData.append('product_id', productId);
            formData.append('partner_id', partnerId);
            formData.append('vendor_data', JSON.stringify(vendorData));

            console.log('Sending request to API...');

            fetch('/erp/api/product_vendors.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status, response.statusText);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return response.text().then(text => {
                        console.log('Raw response:', text);

                        if (!text) {
                            throw new Error('Empty response from server');
                        }

                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response text that failed to parse:', text);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed response data:', data);

                    if (data.success) {
                        showAlert('Vendor added successfully!', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('vendorModal')).hide();
                        resetVendorModal();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('Error: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showAlert('Error saving vendor: ' + error.message, 'error');
                })
                .finally(() => {
                    // Restore button state
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }

        function createNewVendorAndLink(productId, newVendorData, vendorData) {
            console.log('Creating new vendor and linking:', {
                productId,
                newVendorData,
                vendorData
            });

            // Show loading state
            const saveBtn = document.getElementById('saveVendorBtn');
            if (!saveBtn) {
                showAlert('Save button not found', 'error');
                return;
            }

            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
            saveBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'create_vendor_and_link');
            formData.append('product_id', productId);
            formData.append('new_vendor_data', JSON.stringify(newVendorData));
            formData.append('vendor_data', JSON.stringify(vendorData));

            console.log('Sending create vendor request...');

            fetch('/erp/api/product_vendors.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status, response.statusText);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return response.text().then(text => {
                        console.log('Raw response:', text);

                        if (!text) {
                            throw new Error('Empty response from server');
                        }

                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response text that failed to parse:', text);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed response data:', data);

                    if (data.success) {
                        showAlert('New vendor created and linked successfully!', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('vendorModal')).hide();
                        resetVendorModal();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('Error: ' + (data.message || 'Unknown error'), 'error');
                        // Even if linking failed, vendor was created
                        if (data.partner_id) {
                            console.log('Vendor created with ID:', data.partner_id);
                        }
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showAlert('Error creating vendor: ' + error.message, 'error');
                })
                .finally(() => {
                    // Restore button state
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }

        function deleteProductVendor(vendorId) {
            console.log('Deleting vendor:', vendorId);

            if (!confirm('Are you sure you want to remove this vendor from the product?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'remove_vendor');
            formData.append('vendor_id', vendorId);

            fetch('/erp/api/product_vendors.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(text => {
                    console.log('Delete response:', text);
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            showAlert('Vendor removed successfully!', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showAlert('Error: ' + (data.message || 'Unknown error'), 'error');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        showAlert('Error removing vendor: Invalid server response', 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showAlert('Error removing vendor: ' + error.message, 'error');
                });
        }
    <?php endif; ?>
</script>

<?php
ob_end_flush();
require_once __DIR__ . '/../../templates/footer.php';
?>