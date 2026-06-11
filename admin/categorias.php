<?php
declare(strict_types=1);
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/auth.php';
requireLogin();

$msg = '';
$erro = '';

// Salvar nova categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $nome = trim($_POST['nome'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $id = (int)($_POST['id'] ?? 0);

    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nome));
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE categorias SET nome=:nome, slug=:slug, ativo=:ativo WHERE id=:id');
        $stmt->execute([':nome'=>$nome,':slug'=>$slug,':ativo'=>$ativo,':id'=>$id]);
        $msg = 'Categoria atualizada com sucesso!';
    } else {
        $stmt = $pdo->prepare('INSERT INTO categorias (nome, slug, ativo) VALUES (:nome, :slug, :ativo)');
        $stmt->execute([':nome'=>$nome,':slug'=>$slug,':ativo'=>$ativo]);
        $msg = 'Categoria criada com sucesso!';
    }
}

// Excluir
if (isset($_GET['excluir'])) {
    $pdo->prepare('DELETE FROM categorias WHERE id=:id')->execute([':id'=>(int)$_GET['excluir']]);
    $msg = 'Categoria removida.';
}

// Editar
$editando = null;
if (isset($_GET['editar'])) {
    $editando = $pdo->prepare('SELECT * FROM categorias WHERE id=:id');
    $editando->execute([':id'=>(int)$_GET['editar']]);
    $editando = $editando->fetch();
}

$categorias = $pdo->query('SELECT * FROM categorias ORDER BY nome ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categorias — Admin DS 3D</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
<?php require __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
    <header class="admin-topbar">
        <h2>Categorias</h2>
    </header>
    <div class="admin-content">
        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

        <div class="card">
            <h3><?= $editando ? 'Editar categoria' : 'Nova categoria' ?></h3>
            <form method="post">
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id" value="<?= (int)($editando['id'] ?? 0) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="nome" required
                               value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Slug <small>(gerado automaticamente se vazio)</small></label>
                        <input type="text" name="slug"
                               value="<?= htmlspecialchars($editando['slug'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" id="ativo" name="ativo" value="1"
                           <?= ($editando['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <label for="ativo">Ativa</label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Salvar categoria</button>
                    <?php if ($editando): ?>
                        <a href="/admin/categorias.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Categorias cadastradas</h3>
            <?php if (empty($categorias)): ?>
                <p class="empty-msg">Nenhuma categoria ainda.</p>
            <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Nome</th><th>Slug</th><th>Ativa</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td><?= (int)$cat['id'] ?></td>
                    <td><?= htmlspecialchars($cat['nome']) ?></td>
                    <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                    <td><?= $cat['ativo'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Não</span>' ?></td>
                    <td class="actions">
                        <a href="?editar=<?= (int)$cat['id'] ?>">Editar</a>
                        <a href="?excluir=<?= (int)$cat['id'] ?>" class="danger"
                           onclick="return confirm('Excluir esta categoria?')">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
