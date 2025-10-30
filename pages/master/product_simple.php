<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/product.php';

$pageTitle = "Simple Product Test";
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';

// Handle simple form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $simpleData = [
        'product_name' => $_POST['product_name'],
        'unit_price' => (float) $_POST['unit_price'],
        'cost_price' => (float) $_POST['cost_price'],
        'stock_quantity' => (int) $_POST['stock_quantity']
    ];
    
    error_log("Simple form data: " . print_r($simpleData, true));
    
    $result = Product::create($simpleData);
    
    if ($result) {
        echo "<div class='alert alert-success'>✅ Product created successfully! ID: " . $result . "</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Failed to create product! Check error logs.</div>";
    }
}
?>

<main class="main-content">
    <header class="top-header">
        <div class="header-left">
            <h1 class="page-title">Simple Product Test</h1>
        </div>
    </header>

    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <h3>Test Product Creation</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" 
                                       value="Test Product <?php echo date('Y-m-d H:i:s'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="unit_price" class="form-label">Unit Price *</label>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" 
                                       value="100000" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Cost Price *</label>
                                <input type="number" class="form-control" id="cost_price" name="cost_price" 
                                       value="80000" step="0.01" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                       value="10" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Test Product</button>
                    <a href="products.php" class="btn btn-secondary">Back to Products</a>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h3>Debug Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Session User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></p>
                <p><strong>Generated Product Code:</strong> <?php echo Product::generateProductCodePublic(); ?></p>
                <p><strong>PHP Error Log:</strong> <?php echo ini_get('error_log'); ?></p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>