<!-- Accounting Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h4 class="card-title mb-0"><i class="fas fa-chart-line text-success me-2"></i>Accounting Configuration</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <form id="accountingForm">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="row">
                        <div class="col-md-6"><?= renderSelectField('inventory_account', 'Inventory Account', []) ?></div>
                        <div class="col-md-6"><?= renderSelectField('cogs_account', 'COGS Account', []) ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><?= renderSelectField('income_account', 'Income Account', []) ?></div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <div><button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Accounting Settings</button></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Accounting Summary</h6>
                        <div class="mb-2">
                            <small class="text-muted">Inventory Value</small>
                            <div class="h6 text-primary">Rp <?= number_format($accountingInfo['total_inventory_value'] ?? 0, 0) ?></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Total COGS</small>
                            <div class="h6 text-danger">Rp <?= number_format($accountingInfo['total_cogs'] ?? 0, 0) ?></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Total Revenue</small>
                            <div class="h6 text-success">Rp <?= number_format($accountingInfo['total_revenue'] ?? 0, 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>