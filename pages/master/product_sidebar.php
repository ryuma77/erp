<?php
// Product Status Card
$stock_qty = $product['stock_quantity'] ?? 0;
$max_stock = $product['max_stock_level'] ?? 100;
$stock_percentage = $max_stock > 0 ? min(100, ($stock_qty / $max_stock) * 100) : 0;
$progress_class = $stock_percentage < 30 ? 'bg-danger' : ($stock_percentage < 70 ? 'bg-warning' : 'bg-success');
?>

<!-- Product Status Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-chart-bar text-info me-2"></i>Product Status</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <small class="text-muted">Stock Status</small>
            <div class="progress mb-2" style="height: 8px;">
                <div class="progress-bar <?= $progress_class ?>" style="width: <?= $stock_percentage ?>%"></div>
            </div>
            <div class="d-flex justify-content-between">
                <small>Current: <?= $stock_qty ?></small>
                <small>Max: <?= $max_stock ?: 'N/A' ?></small>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-6 border-end">
                <div class="h5 text-primary mb-1">Rp <?= number_format($product['unit_price'] ?? 0, 0) ?></div>
                <small class="text-muted">Selling Price</small>
            </div>
            <div class="col-6">
                <div class="h5 text-success mb-1">Rp <?= number_format($product['cost_price'] ?? 0, 0) ?></div>
                <small class="text-muted">Cost Price</small>
            </div>
        </div>
    </div>
</div>

<!-- Additional Info Card -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-info-circle text-secondary me-2"></i>Additional Information</h5>
    </div>
    <div class="card-body">
        <div class="mb-2">
            <small class="text-muted">Weight</small>
            <div><?= $product['weight'] ? $product['weight'] . ' kg' : 'Not set' ?></div>
        </div>
        <div class="mb-2">
            <small class="text-muted">Dimensions</small>
            <div><?= $product['dimensions'] ?: 'Not set' ?></div>
        </div>
        <div class="mb-2">
            <small class="text-muted">Supplier ID</small>
            <div><?= $product['supplier_id'] ?: 'Not assigned' ?></div>
        </div>
        <?php if ($isEdit): ?>
        <div class="mb-2">
            <small class="text-muted">Last Updated</small>
            <div><?= date('M j, Y H:i', strtotime($product['updated_at'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>