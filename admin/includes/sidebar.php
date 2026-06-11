<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <img src="/assets/img/icone.svg" alt="DS" width="36" height="36">
        <span>DS 3D <strong>Admin</strong></span>
    </div>
    <nav class="sidebar-nav">
        <a href="/admin/dashboard.php" <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'class="active"' : '' ?>>Dashboard</a>
        <a href="/admin/categorias.php" <?= basename($_SERVER['PHP_SELF']) === 'categorias.php' ? 'class="active"' : '' ?>>Categorias</a>
        <a href="/admin/modelos.php" <?= basename($_SERVER['PHP_SELF']) === 'modelos.php' ? 'class="active"' : '' ?>>Modelos</a>
    </nav>
    <div class="sidebar-footer">
        <a href="/" target="_blank">Ver site</a>
        <a href="/admin/logout.php" class="logout">Sair</a>
    </div>
</aside>
