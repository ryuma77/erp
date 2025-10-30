<?php
// Start output buffering at the VERY beginning
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/product.php';
require_once __DIR__ . '/../../includes/unit.php'; // TAMBAH INI


// Initialize variables
$product = null;
$isEdit = false;
$categories = Product::getCategories();
$units = UnitOfMeasure::getAll(); // TAMBAH INI

// Check if editing existing product
if (isset($_GET['id'])) {
    $product = Product::getById($_GET['id']);
    if ($product) {
        $isEdit = true;
        $pageTitle = "Edit Product - " . htmlspecialchars($product['product_name']);
    } else {
        setFlashMessage('error', 'Product not found!');
        header('Location: products.php');
        exit;
    }
} else {
    $pageTitle = "Add New Product";
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

                            <!-- Supplier ID Field (Optional - bisa diisi nanti) -->
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

        </div>
    </div>
</main>

<script>
    // Auto-generate product code if empty and not in edit mode
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!$isEdit): ?>
            const productCodeInput = document.getElementById('product_code');
            if (productCodeInput && !productCodeInput.value) {
                // You can implement auto-generation logic here
                // For now, we'll leave it empty for the backend to handle
            }
        <?php endif; ?>

        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const unitPrice = parseFloat(document.getElementById('unit_price').value);
            const costPrice = parseFloat(document.getElementById('cost_price').value);

            if (costPrice > unitPrice) {
                e.preventDefault();
                alert('Warning: Cost price is higher than unit price. This may result in negative profit.');
            }
        });
    });
</script>

<?php
// End output buffering and flush
ob_end_flush();
require_once __DIR__ . '/../../templates/footer.php';
?>