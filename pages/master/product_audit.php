<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/product.php';
require_once __DIR__ . '/../../includes/audit.php';

if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Product ID is required!');
    header('Location: products.php');
    exit;
}

$productId = $_GET['id'];
$product = Product::getById($productId);

if (!$product) {
    setFlashMessage('error', 'Product not found!');
    header('Location: products.php');
    exit;
}

$auditLogs = AuditTrail::getAuditLog('products', $productId);
$pageTitle = "Audit Trail - " . htmlspecialchars($product['product_name']);

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
    </header>

    <div class="page-content">
        <?php displayFlashMessage(); ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Audit Trail</h3>
                <div class="card-actions">
                    <a href="product_form.php?id=<?php echo $productId; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit Product
                    </a>
                    <a href="products.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($auditLogs)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h4>No Audit Records Found</h4>
                        <p class="text-muted">No changes have been made to this product yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Action</th>
                                    <th>Changed By</th>
                                    <th>Changes</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i:s', strtotime($log['changed_at'])); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php echo $log['action'] === 'CREATE' ? 'bg-success' : 
                                                   ($log['action'] === 'UPDATE' ? 'bg-primary' : 'bg-danger'); ?>">
                                            <?php echo $log['action']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['changed_by_name'] ?? 'System'); ?></td>
                                    <td>
                                        <?php if ($log['action'] === 'CREATE'): ?>
                                            <span class="text-success">Product created</span>
                                        <?php elseif ($log['action'] === 'UPDATE' && $log['new_values']): ?>
                                            <?php
                                            $changes = json_decode($log['new_values'], true);
                                            $changeList = [];
                                            foreach ($changes as $field => $value) {
                                                if ($field !== 'updated_by' && $field !== 'updated_at') {
                                                    $changeList[] = "<strong>$field</strong>: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value);
                                                }
                                            }
                                            echo implode('<br>', $changeList);
                                            ?>
                                        <?php elseif ($log['action'] === 'DELETE'): ?>
                                            <span class="text-danger">Product deleted</span>
                                        <?php elseif ($log['action'] === 'SOFT_DELETE'): ?>
                                            <span class="text-warning">Product deactivated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo $log['ip_address']; ?></small></td>
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

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>