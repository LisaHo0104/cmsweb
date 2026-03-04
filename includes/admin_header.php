<?php
// Each admin page sets $admin_page_title and $admin_active_nav before including this file.
// Fall back to safe defaults so the layout never breaks if they're forgotten.
$admin_page_title = $admin_page_title ?? 'Admin';
$admin_active_nav = $admin_active_nav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($admin_page_title); ?> — Gong Cha Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
</head>
<body>

<div class="adm-layout">

    <!-- Sidebar -->
    <aside class="adm-sidebar">
        <div class="adm-sidebar__brand">
            <span class="adm-sidebar__logo"><i class="fas fa-mug-hot"></i></span>
            <img src="<?php echo ASSETS_URL; ?>/images/gongcha-logo-new.svg" alt="Gong Cha" class="adm-sidebar__logo-img">
        </div>

        <nav class="adm-sidebar__nav">
            <a href="/cmsweb/admin/menu.php" class="adm-sidebar__link <?php echo $admin_active_nav === 'menu' ? 'adm-sidebar__link--active' : ''; ?>">
                <i class="fas fa-mug-hot"></i>
                <span>Menu</span>
            </a>
            <a href="/cmsweb/admin/about.php" class="adm-sidebar__link <?php echo $admin_active_nav === 'about' ? 'adm-sidebar__link--active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span>About Page</span>
            </a>
        </nav>

        <div class="adm-sidebar__footer">
            <div class="adm-sidebar__user">
                <div class="adm-sidebar__avatar"><i class="fas fa-user"></i></div>
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            </div>
            <a href="/cmsweb/admin/logout.php" class="adm-sidebar__logout" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </aside>

    <!-- Main content area -->
    <div class="adm-main">

        <!-- Topbar -->
        <header class="adm-topbar">
            <h1 class="adm-topbar__title"><?php echo htmlspecialchars($admin_page_title); ?></h1>
            <div class="adm-topbar__actions">
                <a href="/cmsweb/public/about.php" target="_blank" class="adm-btn adm-btn--ghost">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>
            </div>
        </header>

        <!-- Page content starts here -->
        <div class="adm-content">
