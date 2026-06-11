<?php
declare(strict_types=1);
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/auth.php';
requireLogin();

$totalModelos    = $pdo->query('SELECT COUNT(*) FROM modelos')->fetchColumn();
$totalCategorias = $pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
$totalOrcamentos = $pdo->query('SELECT COUNT(*) FROM orcamentos')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Admin DS 3D</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
<?php require __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
    <header class="admin-topbar">
        <h2>Dashboard</h2>
        <span>Olá, <strong><?= htmlspecialchars($_SESSION['admin_nome']) ?></strong></span>
    </header>
    <div class="admin-content">
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Modelos cadastrados</span>
                <strong class="stat-value"><?= (int)$totalModelos ?></strong>
            </div>
            <div class="stat-card">
                <span class="stat-label">Categorias</span>
                <strong class="stat-value"><?= (int)$totalCategorias ?></strong>
            </div>
            <div class="stat-card">
                <span class="stat-label">Orçamentos recebidos</span>
                <strong class="stat-value"><?= (int)$totalOrcamentos ?></strong>
            </div>
        </div>
        <div class="quick-links">
            <a href="/admin/categorias.php" class="btn btn-secondary">Gerenciar categorias</a>
            <a href="/admin/modelos.php" class="btn btn-primary">Gerenciar modelos</a>
        </div>
    </div>
</div>
</body>
</html>
