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
                                                        <strong class="small"><?= htmlspecialchars($vendor['vendor_name']) ?></strong>
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

<!-- Vendor Modal (sama seperti sebelumnya) -->
<div class="modal fade" id="vendorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="vendorForm">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Vendor Name *</label>
                        <input type="text" class="form-control" name="vendor_name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cost Price</label>
                                <input type="number" class="form-control" name="cost_price" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Lead Time (Days)</label>
                                <input type="number" class="form-control" name="lead_time_days" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary">
                        <label class="form-check-label">Set as Primary Vendor</label>
                    </div>
                </form>
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
        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const unitPrice = parseFloat(document.getElementById('unit_price').value);
            const costPrice = parseFloat(document.getElementById('cost_price').value);
            if (costPrice > unitPrice) {
                e.preventDefault();
                alert('Warning: Cost price is higher than unit price. This may result in negative profit.');
            }
        });

        // Vendor modal
        <?php if ($isEdit): ?>
            document.getElementById('saveVendorBtn').addEventListener('click', function() {
                const vendorName = document.querySelector('input[name="vendor_name"]').value;
                if (!vendorName) {
                    alert('Please enter vendor name');
                    return;
                }
                alert('Vendor "' + vendorName + '" will be saved.');
                bootstrap.Modal.getInstance(document.getElementById('vendorModal')).hide();
                document.getElementById('vendorForm').reset();
            });
        <?php endif; ?>
    });
</script>

<?php
ob_end_flush();
require_once __DIR__ . '/../../templates/footer.php';
?>