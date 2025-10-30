<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Check authentication for protected pages
if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
    Auth::requireAuth();
}

$currentUser = Auth::getUser();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/admin.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Debug CSS Path -->
    <script>
        console.log('Base URL:', '<?php echo BASE_URL ?? "Not defined"; ?>');
        console.log('CSS Path:', '<?php echo BASE_URL ?? ""; ?>/assets/css/admin.css');
    </script>

</head>

<body>
    <?php if (Auth::isLoggedIn()): ?>
        <div class="admin-container">
            <!-- Sidebar will be included here -->
        <?php endif; ?>