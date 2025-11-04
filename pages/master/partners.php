<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/partner.php';

$pageTitle = "Partner Management";
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        // Soft delete partner
        $result = Partner::update($_POST['delete_id'], ['is_active' => false]);
        if ($result) {
            setFlashMessage('success', 'Partner deleted successfully!');
        } else {
            setFlashMessage('error', 'Failed to delete partner!');
        }
        header('Location: partner.php');
        exit;
    }
}

// Get all partners with pagination
$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$result = Partner::getAll($page, 20, $search);
$partners = $result['partners'];
$pagination = $result;
?>

<main class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title">Master Data - Partners</h1>
        </div>

        <div class="header-right">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search partners..." id="searchInput"
                    value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="header-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>

                <div class="user-dropdown">
                    <button class="user-menu-btn">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentUser['name']); ?>&background=007bff&color=fff" alt="<?= htmlspecialchars($currentUser['name']); ?>">
                        <span><?= htmlspecialchars($currentUser['name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="page-content">
        <?php displayFlashMessage(); ?>

        <div class="page-header">
            <h2>Partner Management</h2>
            <a href="partners_form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add New Partner
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Partner List</h3>
                <div class="card-actions">
                    <button class="btn btn-outline-secondary btn-sm" onclick="exportPartners()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-upload"></i> Import
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($partners)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>No Partners Found</h4>
                        <p class="text-muted">Get started by adding your first partner.</p>
                        <a href="partners_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Partner
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="partnersTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Partner Name</th>
                                    <th>Type</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Credit Limit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partners as $partner): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($partner['partner_code']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($partner['partner_name']); ?></div>
                                            <?php if ($partner['tax_id']): ?>
                                                <small class="text-muted">Tax: <?= htmlspecialchars($partner['tax_id']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $partner['partner_type'] == 'vendor' ? 'bg-warning' : ($partner['partner_type'] == 'customer' ? 'bg-info' : 'bg-primary'); ?>">
                                                <?= ucfirst($partner['partner_type']); ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($partner['contact_person'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($partner['phone'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($partner['email'] ?? '-'); ?></td>
                                        <td class="text-end">
                                            <?php if ($partner['credit_limit'] > 0): ?>
                                                Rp <?= number_format($partner['credit_limit'], 0, ',', '.'); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $partner['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?= $partner['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="partner_form.php?id=<?= $partner['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="confirmDelete(<?= $partner['id']; ?>, '<?= htmlspecialchars($partner['partner_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($pagination['page'] > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $pagination['page'] - 1; ?>&search=<?= urlencode($search); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <li class="page-item <?= $i == $pagination['page'] ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $pagination['page'] + 1; ?>&search=<?= urlencode($search); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
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
                <p>Are you sure you want to delete partner "<strong id="deletePartnerName"></strong>"?</p>
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
        document.getElementById('deletePartnerName').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function exportPartners() {
        alert('Export feature will be implemented soon!');
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = this.value;
            window.location.href = '?search=' + encodeURIComponent(searchTerm);
        }
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>