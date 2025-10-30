<?php
$pageTitle = "Page Title"; // Ganti dengan judul halaman
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
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
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
            <h2><?php echo $pageTitle; ?></h2>
            <button class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add New
            </button>
        </div>
        
        <div class="card">
            <div class="card-body">
                <p>Halaman <?php echo $pageTitle; ?> akan ditampilkan di sini.</p>
                <p>Fitur dalam pengembangan...</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>