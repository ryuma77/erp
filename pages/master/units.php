<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/unit.php';

$pageTitle = "Unit of Measure Management";
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $result = UnitOfMeasure::softDelete($_POST['delete_id']);
        if ($result) {
            setFlashMessage('success', 'Unit deleted successfully!');
        } else {
            setFlashMessage('error', 'Failed to delete unit! It might be used in products.');
        }
        header('Location: units.php');
        exit;
    }
}

// Get all units
$units = UnitOfMeasure::getAll();
?>

<main class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title">Master Data - Unit of Measure</h1>
        </div>
        
        <div class="header-right">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search units..." id="searchInput">
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
            <h2>Unit of Measure Management</h2>
            <a href="unit_form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add New Unit
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Unit List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($units)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
                        <h4>No Units Found</h4>
                        <p class="text-muted">Get started by adding your first unit of measure.</p>
                        <a href="unit_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Unit
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="unitsTable">
                            <thead>
                                <tr>
                                    <th>Unit Code</th>
                                    <th>Unit Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($units as $unit): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($unit['unit_code']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                                    <td><?php echo htmlspecialchars($unit['description'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $unit['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $unit['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="unit_form.php?id=<?php echo $unit['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $unit['id']; ?>, '<?php echo htmlspecialchars($unit['unit_name']); ?>')">
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
                <p>Are you sure you want to delete unit "<strong id="deleteUnitName"></strong>"?</p>
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
    document.getElementById('deleteUnitName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Simple search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#unitsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>