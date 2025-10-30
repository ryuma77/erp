<!-- Transactions Section -->
<div class="card">
    <div class="card-header bg-light">
        <h4 class="card-title mb-0">
            <i class="fas fa-exchange-alt text-info me-2"></i>Product Transactions
            <span class="badge bg-info ms-2"><?= count($transactions) ?> records</span>
        </h4>
    </div>
    <div class="card-body">
        <?php if (!empty($transactions)): ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
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
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($transaction['transaction_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $transaction['transaction_type'] == 'purchase' ? 'success' : ($transaction['transaction_type'] == 'sale' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($transaction['transaction_type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($transaction['document_no']) ?></td>
                            <td class="<?= $transaction['quantity'] < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= $transaction['quantity'] > 0 ? '+' : '' ?><?= $transaction['quantity'] ?>
                            </td>
                            <td>Rp <?= number_format($transaction['unit_price'], 0) ?></td>
                            <td>Rp <?= number_format($transaction['total_amount'], 0) ?></td>
                            <td><?= htmlspecialchars($transaction['reference'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Transactions Found</h5>
                <p class="text-muted">Transaction history will appear here once the product has activity</p>
            </div>
        <?php endif; ?>
    </div>
</div>