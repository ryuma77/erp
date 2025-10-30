<!-- Vendors Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">
                <i class="fas fa-truck text-warning me-2"></i>Product Venders
                <span class="badge bg-warning ms-2"><?= count($productVendors) ?> vendors</span>
            </h4>
            <button type="button" class="btn btn-warning btn-sm" id="addVendorBtn">
                <i class="fas fa-plus me-1"></i> Add Vendor
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($productVendors)): ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Vendor Name</th>
                            <th>Contact</th>
                            <th>Lead Time</th>
                            <th>Cost Price</th>
                            <th>MOQ</th>
                            <th>Status</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productVendors as $vendor): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($vendor['vendor_name']) ?></strong><?= $vendor['is_primary'] ? ' <span class="badge bg-success ms-1">Primary</span>' : '' ?></td>
                            <td><small><?= htmlspecialchars($vendor['contact_person'] ?? 'N/A') ?></small><br><small class="text-muted"><?= htmlspecialchars($vendor['contact_phone'] ?? '') ?></small></td>
                            <td><?= $vendor['lead_time_days'] ?> days</td>
                            <td>Rp <?= number_format($vendor['cost_price'], 0) ?></td>
                            <td><?= $vendor['moq'] ?></td>
                            <td><span class="badge bg-success">Active</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary edit-vendor" data-vendor-id="<?= $vendor['id'] ?>"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-outline-danger delete-vendor" data-vendor-id="<?= $vendor['id'] ?>"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Vendors Added</h5>
                <p class="text-muted">Add vendors to manage your product suppliers</p>
                <button type="button" class="btn btn-warning" id="addVendorBtn2"><i class="fas fa-plus me-1"></i> Add First Vendor</button>
            </div>
        <?php endif; ?>
    </div>
</div>