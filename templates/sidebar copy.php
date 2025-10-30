<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$sidebarCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
?>
<aside class="sidebar <?php echo $sidebarCollapsed ? 'collapsed' : ''; ?>" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-chart-line"></i>
            <span class="logo-text"><?php echo APP_NAME; ?></span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">

            <!-- ==================== -->
            <!-- DASHBOARD & ANALYTIC -->
            <!-- ==================== -->
            <li class="nav-section">
                <span class="nav-section-text">DASHBOARD & ANALYTIC</span>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/analytics/index.php" class="nav-link <?php echo str_contains($currentPage, 'analytics') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Analytics</span>
                </a>
            </li>

            <!-- ============ -->
            <!-- MASTER DATA -->
            <!-- ============ -->
            <li class="nav-section">
                <span class="nav-section-text">MASTER DATA</span>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/master/products.php" class="nav-link <?php echo str_contains($currentPage, 'products') ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span class="nav-text">Product</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/master/partners.php" class="nav-link <?php echo str_contains($currentPage, 'partners') ? 'active' : ''; ?>">
                    <i class="fas fa-handshake"></i>
                    <span class="nav-text">Partner</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/master/categories.php" class="nav-link <?php echo str_contains($currentPage, 'categories') ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i>
                    <span class="nav-text">Product Categories</span>
                </a>
            </li>

            <!-- ============= -->
            <!-- TRANSACTION -->
            <!-- ============= -->
            <li class="nav-section">
                <span class="nav-section-text">TRANSACTION</span>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/transaction/purchase.php" class="nav-link <?php echo str_contains($currentPage, 'purchase') ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="nav-text">Purchase</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/transaction/sales.php" class="nav-link <?php echo str_contains($currentPage, 'sales') ? 'active' : ''; ?>">
                    <i class="fas fa-cash-register"></i>
                    <span class="nav-text">Sales</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/transaction/inventory.php" class="nav-link <?php echo str_contains($currentPage, 'inventory') ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i>
                    <span class="nav-text">Inventory</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/transaction/manufacturing.php" class="nav-link <?php echo str_contains($currentPage, 'manufacturing') ? 'active' : ''; ?>">
                    <i class="fas fa-industry"></i>
                    <span class="nav-text">Manufacturing</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/transaction/accounting.php" class="nav-link <?php echo str_contains($currentPage, 'accounting') ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span class="nav-text">Accounting</span>
                </a>
            </li>

            <!-- ================ -->
            <!-- CONFIGURATION -->
            <!-- ================ -->
            <li class="nav-section">
                <span class="nav-section-text">CONFIGURATION</span>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/config/setup.php" class="nav-link <?php echo str_contains($currentPage, 'setup') ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i>
                    <span class="nav-text">General Setup</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/config/tax.php" class="nav-link <?php echo str_contains($currentPage, 'tax') ? 'active' : ''; ?>">
                    <i class="fas fa-percent"></i>
                    <span class="nav-text">Tax</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/config/chart_of_accounts.php" class="nav-link <?php echo str_contains($currentPage, 'chart_of_accounts') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span class="nav-text">Chart of Account</span>
                </a>
            </li>

            <!-- ======= -->
            <!-- SYSTEM -->
            <!-- ======= -->
            <li class="nav-section">
                <span class="nav-section-text">SYSTEM</span>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/users/list.php" class="nav-link <?php echo str_contains($currentPage, 'users') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">User Management</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/profile/index.php" class="nav-link <?php echo str_contains($currentPage, 'profile') ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span class="nav-text">Profile</span>
                </a>
            </li>

        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['name']); ?>&background=007bff&color=fff" alt="<?php echo htmlspecialchars($currentUser['name']); ?>">
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                <span class="user-role"><?php echo ucfirst($currentUser['role']); ?></span>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span class="nav-text">Logout</span>
        </a>
    </div>
</aside>