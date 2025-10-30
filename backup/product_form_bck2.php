<?php
// Start output buffering at the VERY beginning
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/product.php';
require_once __DIR__ . '/../../includes/unit.php';

// Initialize variables
$product = null;
$isEdit = false;
$categories = Product::getCategories();
$units = UnitOfMeasure::getAll();

// Check if editing existing product
if (isset($_GET['id'])) {
    $product = Product::getById($_GET['id']);
    if ($product) {
        $isEdit = true;
        $pageTitle = "Edit Product - " . htmlspecialchars($product['product_name']);

        // Load additional data for tabs if editing
        $productVendors = $isEdit ? Product::getProductVendors($product['id']) : [];
        $transactions = $isEdit ? Product::getProductTransactions($product['id']) : [];
        $accountingInfo = $isEdit ? Product::getAccountingInfo($product['id']) : [];
    } else {
        setFlashMessage('error', 'Product not found!');
        header('Location: products.php');
        exit;
    }
} else {
    $pageTitle = "Add New Product";
    $productVendors = [];
    $transactions = [];
    $accountingInfo = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'product_code' => sanitizeInput($_POST['product_code'] ?? ''),
        'product_name' => sanitizeInput($_POST['product_name'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'category_id' => $_POST['category_id'] ?? null,
        'unit_price' => (float) ($_POST['unit_price'] ?? 0),
        'cost_price' => (float) ($_POST['cost_price'] ?? 0),
        'unit_id' => $_POST['unit_id'] ?? null,
        'stock_quantity' => (int) ($_POST['stock_quantity'] ?? 0),
        'min_stock_level' => (int) ($_POST['min_stock_level'] ?? 0),
        'max_stock_level' => !empty($_POST['max_stock_level']) ? (int) $_POST['max_stock_level'] : null,
        'barcode' => sanitizeInput($_POST['barcode'] ?? ''),
        'sku' => sanitizeInput($_POST['sku'] ?? ''),
        'weight' => !empty($_POST['weight']) ? (float) $_POST['weight'] : null,
        'dimensions' => sanitizeInput($_POST['dimensions'] ?? ''),
        'supplier_id' => $_POST['supplier_id'] ?? null,
        'is_active' => isset($_POST['is_active'])
    ];

    if ($isEdit) {
        $result = Product::update($_POST['id'], $data);
        $message = $result ? 'Product updated successfully!' : 'Failed to update product!';
    } else {
        $result = Product::create($data);
        $message = $result ? 'Product created successfully!' : 'Failed to create product!';
    }

    if ($result) {
        setFlashMessage('success', $message);
        ob_end_clean();
        header('Location: products.php');
        exit;
    } else {
        $errorMessage = $message . ' Please check the form data and try again.';
        setFlashMessage('error', $errorMessage);
        $product = array_merge($product ?: [], $data);
    }
}

// Set page title after potential redirect
$pageTitle = $pageTitle ?? "Product Form";

// Now output the HTML
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>

<main class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title"><?php echo $pageTitle; ?></h1>
        </div>

        <div class="header-right">
            <div class="header-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>

                <div class="user-dropdown">
                    <button class="user-menu-btn">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['name']); ?>&background=007bff&color=fff" alt="<?php echo htmlspecialchars($currentUser['name']); ?>">
                        <span><?php echo htmlspecialchars($currentUser['name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="page-content">
        <?php displayFlashMessage(); ?>

        <div class="card">
            <div class="card-header">
                <h3><?php echo $isEdit ? 'Edit Product' : 'Add New Product'; ?></h3>
                <a href="products.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
            <div class="card-body">

                <!-- TAB NAVIGATION -->
                <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab"
                            data-bs-target="#basic" type="button" role="tab"
                            aria-controls="basic" aria-selected="true">
                            <i class="fas fa-info-circle me-1"></i> Basic Information
                        </button>
                    </li>

                    <?php if ($isEdit): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="vendor-tab" data-bs-toggle="tab"
                                data-bs-target="#vendor" type="button" role="tab"
                                aria-controls="vendor" aria-selected="false">
                                <i class="fas fa-truck me-1"></i> Vendors
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="accounting-tab" data-bs-toggle="tab"
                                data-bs-target="#accounting" type="button" role="tab"
                                aria-controls="accounting" aria-selected="false">
                                <i class="fas fa-chart-line me-1"></i> Accounting
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="transaction-tab" data-bs-toggle="tab"
                                data-bs-target="#transaction" type="button" role="tab"
                                aria-controls="transaction" aria-selected="false">
                                <i class="fas fa-exchange-alt me-1"></i> Transactions
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- TAB CONTENT -->
                <div class="tab-content" id="productTabsContent">

                    <!-- TAB 1: BASIC INFORMATION -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                        <form method="POST" id="productForm">
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="product_code" class="form-label">Product Code *</label>
                                        <input type="text" class="form-control" id="product_code" name="product_code"
                                            value="<?php echo htmlspecialchars($product['product_code'] ?? ''); ?>"
                                            required <?php echo $isEdit ? 'readonly' : ''; ?>>
                                        <div class="form-text">Product code will be auto-generated if left empty</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Product Name *</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name"
                                            value="<?php echo htmlspecialchars($product['product_name'] ?? ''); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">-- Select Category --</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"
                                                    <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="unit_id" class="form-label">Unit of Measure *</label>
                                        <select class="form-select" id="unit_id" name="unit_id" required>
                                            <option value="">-- Select Unit --</option>
                                            <?php foreach ($units as $unit): ?>
                                                <option value="<?php echo $unit['id']; ?>"
                                                    <?php echo ($product['unit_id'] ?? '') == $unit['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($unit['unit_code']); ?> - <?php echo htmlspecialchars($unit['unit_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="unit_price" class="form-label">Unit Price *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="unit_price" name="unit_price"
                                                        value="<?php echo $product['unit_price'] ?? '0'; ?>" step="0.01" min="0" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="cost_price" class="form-label">Cost Price *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="cost_price" name="cost_price"
                                                        value="<?php echo $product['cost_price'] ?? '0'; ?>" step="0.01" min="0" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity"
                                                    value="<?php echo $product['stock_quantity'] ?? '0'; ?>" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="min_stock_level" class="form-label">Min Stock Level</label>
                                                <input type="number" class="form-control" id="min_stock_level" name="min_stock_level"
                                                    value="<?php echo $product['min_stock_level'] ?? '0'; ?>" min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="max_stock_level" class="form-label">Max Stock Level</label>
                                        <input type="number" class="form-control" id="max_stock_level" name="max_stock_level"
                                            value="<?php echo $product['max_stock_level'] ?? ''; ?>" min="0">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode" class="form-label">Barcode</label>
                                                <input type="text" class="form-control" id="barcode" name="barcode"
                                                    value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="sku" class="form-label">SKU</label>
                                                <input type="text" class="form-control" id="sku" name="sku"
                                                    value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="weight" class="form-label">Weight (kg)</label>
                                                <input type="number" class="form-control" id="weight" name="weight"
                                                    value="<?php echo $product['weight'] ?? ''; ?>" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="dimensions" class="form-label">Dimensions</label>
                                                <input type="text" class="form-control" id="dimensions" name="dimensions"
                                                    value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>"
                                                    placeholder="L x W x H">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="supplier_id" class="form-label">Supplier ID</label>
                                        <input type="number" class="form-control" id="supplier_id" name="supplier_id"
                                            value="<?php echo $product['supplier_id'] ?? ''; ?>" min="0"
                                            placeholder="Optional - will be implemented later">
                                        <div class="form-text">Supplier management will be implemented in Partner module</div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                                <?php echo ($product['is_active'] ?? true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">Active Product</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <?php echo $isEdit ? 'Update Product' : 'Create Product'; ?>
                                    </button>
                                    <a href="products.php" class="btn btn-secondary">Cancel</a>

                                    <?php if ($isEdit): ?>
                                        <a href="product_audit.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-info">
                                            <i class="fas fa-history"></i> View Audit Trail
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 2: VENDORS (Hanya tampil jika edit) -->
                    <?php if ($isEdit): ?>
                        <div class="tab-pane fade" id="vendor" role="tabpanel" aria-labelledby="vendor-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Product Vendors</h5>
                                <button type="button" class="btn btn-primary btn-sm" id="addVendorBtn">
                                    <i class="fas fa-plus"></i> Add Vendor
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="vendorsTable">
                                    <thead>
                                        <tr>
                                            <th>Vendor Name</th>
                                            <th>Contact Person</th>
                                            <th>Lead Time (Days)</th>
                                            <th>Cost Price</th>
                                            <th>MOQ</th>
                                            <th>Primary</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($productVendors)): ?>
                                            <?php foreach ($productVendors as $vendor): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($vendor['vendor_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($vendor['contact_person'] ?? 'N/A'); ?></td>
                                                    <td><?php echo $vendor['lead_time_days']; ?> days</td>
                                                    <td>Rp <?php echo number_format($vendor['cost_price'], 2); ?></td>
                                                    <td><?php echo $vendor['moq']; ?></td>
                                                    <td>
                                                        <?php if ($vendor['is_primary']): ?>
                                                            <span class="badge bg-success">Primary</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary edit-vendor"
                                                            data-vendor-id="<?php echo $vendor['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger delete-vendor"
                                                            data-vendor-id="<?php echo $vendor['id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    No vendors found. Click "Add Vendor" to add one.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: ACCOUNTING (Hanya tampil jika edit) -->
                        <div class="tab-pane fade" id="accounting" role="tabpanel" aria-labelledby="accounting-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Chart of Accounts Mapping</h5>
                                    <form id="accountingForm">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                                        <div class="mb-3">
                                            <label for="inventory_account" class="form-label">Inventory Account</label>
                                            <select class="form-select" id="inventory_account" name="inventory_account">
                                                <option value="">-- Select Inventory Account --</option>
                                                <!-- Accounts akan di-load via JavaScript -->
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="cogs_account" class="form-label">COGS Account</label>
                                            <select class="form-select" id="cogs_account" name="cogs_account">
                                                <option value="">-- Select COGS Account --</option>
                                                <!-- Accounts akan di-load via JavaScript -->
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="income_account" class="form-label">Income Account</label>
                                            <select class="form-select" id="income_account" name="income_account">
                                                <option value="">-- Select Income Account --</option>
                                                <!-- Accounts akan di-load via JavaScript -->
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Save Accounting Settings
                                        </button>
                                    </form>
                                </div>

                                <div class="col-md-6">
                                    <h5>Accounting Information</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="mb-2">
                                                <strong>Total Inventory Value:</strong>
                                                <span class="float-end">Rp <?php echo number_format($accountingInfo['total_inventory_value'] ?? 0, 2); ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Average Cost:</strong>
                                                <span class="float-end">Rp <?php echo number_format($accountingInfo['average_cost'] ?? 0, 2); ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Last Purchase Price:</strong>
                                                <span class="float-end">Rp <?php echo number_format($accountingInfo['last_purchase_price'] ?? 0, 2); ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Total COGS:</strong>
                                                <span class="float-end">Rp <?php echo number_format($accountingInfo['total_cogs'] ?? 0, 2); ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Total Revenue:</strong>
                                                <span class="float-end">Rp <?php echo number_format($accountingInfo['total_revenue'] ?? 0, 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: TRANSACTIONS (Hanya tampil jika edit) -->
                        <div class="tab-pane fade" id="transaction" role="tabpanel" aria-labelledby="transaction-tab">
                            <h5>Product Transactions</h5>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <select class="form-select" id="transactionTypeFilter">
                                        <option value="">All Transaction Types</option>
                                        <option value="purchase">Purchase</option>
                                        <option value="sale">Sale</option>
                                        <option value="adjustment">Adjustment</option>
                                        <option value="transfer">Transfer</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="date" class="form-control" id="startDateFilter" placeholder="Start Date">
                                </div>
                                <div class="col-md-4">
                                    <input type="date" class="form-control" id="endDateFilter" placeholder="End Date">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="transactionsTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Document No</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total Amount</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($transactions)): ?>
                                            <?php foreach ($transactions as $transaction): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                                                echo $transaction['transaction_type'] == 'purchase' ? 'success' : ($transaction['transaction_type'] == 'sale' ? 'danger' : 'warning');
                                                                                ?>">
                                                            <?php echo ucfirst($transaction['transaction_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($transaction['document_no']); ?></td>
                                                    <td class="<?php echo $transaction['quantity'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                                        <?php echo $transaction['quantity'] > 0 ? '+' : ''; ?><?php echo $transaction['quantity']; ?>
                                                    </td>
                                                    <td>Rp <?php echo number_format($transaction['unit_price'], 2); ?></td>
                                                    <td>Rp <?php echo number_format($transaction['total_amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['reference'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    No transactions found for this product.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL ADD/EDIT VENDOR -->
<div class="modal fade" id="vendorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vendorModalTitle">Add Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="vendorForm">
                    <input type="hidden" id="vendor_id" name="vendor_id">
                    <input type="hidden" name="product_id" value="<?php echo $product['id'] ?? ''; ?>">

                    <div class="mb-3">
                        <label for="vendor_name" class="form-label">Vendor Name *</label>
                        <input type="text" class="form-control" id="vendor_name" name="vendor_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person">
                    </div>

                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lead_time_days" class="form-label">Lead Time (Days)</label>
                                <input type="number" class="form-control" id="lead_time_days" name="lead_time_days" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Cost Price</label>
                                <input type="number" class="form-control" id="vendor_cost_price" name="cost_price" step="0.01" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="moq" class="form-label">Minimum Order Quantity</label>
                                <input type="number" class="form-control" id="moq" name="moq" min="0" value="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary">
                                    <label class="form-check-label" for="is_primary">Set as Primary Vendor</label>
                                </div>
                            </div>
                        </div>
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
    // Tab functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tabs
        const triggerTabList = [].slice.call(document.querySelectorAll('#productTabs button'))
        triggerTabList.forEach(function(triggerEl) {
            const tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function(event) {
                event.preventDefault()
                tabTrigger.show()
            })
        });

        // Vendor modal handling
        const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'));

        // Only setup vendor functionality if in edit mode
        <?php if ($isEdit): ?>
            document.getElementById('addVendorBtn').addEventListener('click', function() {
                document.getElementById('vendorModalTitle').textContent = 'Add Vendor';
                document.getElementById('vendorForm').reset();
                document.getElementById('vendor_id').value = '';
                vendorModal.show();
            });

            // Edit vendor buttons
            document.querySelectorAll('.edit-vendor').forEach(button => {
                button.addEventListener('click', function() {
                    const vendorId = this.getAttribute('data-vendor-id');
                    loadVendorData(vendorId);
                });
            });

            // Delete vendor buttons
            document.querySelectorAll('.delete-vendor').forEach(button => {
                button.addEventListener('click', function() {
                    const vendorId = this.getAttribute('data-vendor-id');
                    if (confirm('Are you sure you want to delete this vendor?')) {
                        deleteVendor(vendorId);
                    }
                });
            });

            // Save vendor
            document.getElementById('saveVendorBtn').addEventListener('click', function() {
                saveVendor();
            });

            // Save accounting settings
            document.getElementById('accountingForm').addEventListener('submit', function(e) {
                e.preventDefault();
                saveAccountingSettings();
            });

            // Filter transactions
            document.getElementById('transactionTypeFilter').addEventListener('change', filterTransactions);
            document.getElementById('startDateFilter').addEventListener('change', filterTransactions);
            document.getElementById('endDateFilter').addEventListener('change', filterTransactions);
        <?php endif; ?>

        // Form validation untuk basic form
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const unitPrice = parseFloat(document.getElementById('unit_price').value);
            const costPrice = parseFloat(document.getElementById('cost_price').value);

            if (costPrice > unitPrice) {
                e.preventDefault();
                alert('Warning: Cost price is higher than unit price. This may result in negative profit.');
            }
        });
    });

    <?php if ($isEdit): ?>

        function loadVendorData(vendorId) {
            // Simulasi data - nanti diganti dengan AJAX real
            alert('Load vendor data for ID: ' + vendorId + ' - This will be implemented with real AJAX call');
            // Implementasi AJAX akan ditambahkan nanti
        }

        function saveVendor() {
            const formData = new FormData(document.getElementById('vendorForm'));

            // Simulasi save - nanti diganti dengan AJAX real
            alert('Save vendor - This will be implemented with real AJAX call');
            // Implementasi AJAX akan ditambahkan nanti
        }

        function deleteVendor(vendorId) {
            // Simulasi delete - nanti diganti dengan AJAX real
            alert('Delete vendor ID: ' + vendorId + ' - This will be implemented with real AJAX call');
            // Implementasi AJAX akan ditambahkan nanti
        }

        function saveAccountingSettings() {
            const formData = new FormData(document.getElementById('accountingForm'));

            // Simulasi save - nanti diganti dengan AJAX real
            alert('Save accounting settings - This will be implemented with real AJAX call');
            // Implementasi AJAX akan ditambahkan nanti
        }

        function filterTransactions() {
            const type = document.getElementById('transactionTypeFilter').value;
            const startDate = document.getElementById('startDateFilter').value;
            const endDate = document.getElementById('endDateFilter').value;

            // Simulasi filter - nanti diganti dengan AJAX real
            console.log('Filter transactions:', {
                type,
                startDate,
                endDate
            });
            // Implementasi AJAX akan ditambahkan nanti
        }
    <?php endif; ?>
</script>

<?php
// End output buffering and flush
ob_end_flush();
require_once __DIR__ . '/../../templates/footer.php';
?>