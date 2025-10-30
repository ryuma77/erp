<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/product.php';

$pageTitle = "Product Management";
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $result = Product::softDelete($_POST['delete_id']);
        if ($result) {
            setFlashMessage('success', 'Product deleted successfully!');
        } else {
            setFlashMessage('error', 'Failed to delete product!');
        }
        header('Location: products.php');
        exit;
    }
}

// Get all products
$products = Product::getAll();
?>

<main class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title">Master Data - Products</h1>
        </div>

        <div class="header-right">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search products..." id="searchInput">
            </div>

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

        <div class="page-header">
            <h2>Product Management</h2>
            <a href="product_form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add New Product
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Product List</h3>
                <div class="card-actions">
                    <button class="btn btn-outline-secondary btn-sm" onclick="exportProducts()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-upload"></i> Import
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($products)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h4>No Products Found</h4>
                        <p class="text-muted">Get started by adding your first product.</p>
                        <a href="product_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Product
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Cost</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_code']); ?></strong>
                                            <?php if ($product['barcode']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($product['barcode']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                            <?php if ($product['description']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td class="text-end">Rp <?php echo number_format($product['unit_price'], 0, ',', '.'); ?></td>
                                        <td class="text-end">Rp <?php echo number_format($product['cost_price'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $product['stock_quantity'] <= $product['min_stock_level'] ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                            <?php if ($product['min_stock_level'] > 0): ?>
                                                <br><small class="text-muted">Min: <?php echo $product['min_stock_level']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $product['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete product "<strong id="deleteProductName"></strong>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="delete_id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteProductName').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function exportProducts() {
        alert('Export feature will be implemented soon!');
    }

    // Simple search functionality
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#productsTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>