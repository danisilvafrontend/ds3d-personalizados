<?php
declare(strict_types=1);
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/auth.php';
requireLogin();

$msg = '';
$categorias = $pdo->query('SELECT id, nome FROM categorias WHERE ativo=1 ORDER BY nome')->fetchAll();

// Pasta de imagens dos modelos
define('MODELOS_IMG_DIR', __DIR__ . '/../assets/img/modelos/');
define('MODELOS_IMG_URL', '/assets/img/modelos/');

// Cria a pasta automaticamente se não existir
if (!is_dir(MODELOS_IMG_DIR)) {
    mkdir(MODELOS_IMG_DIR, 0755, true);
}

// Salvar modelo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $id            = (int)($_POST['id'] ?? 0);
    $nome          = trim($_POST['nome'] ?? '');
    $slug          = trim($_POST['slug'] ?? '');
    $categoria_id  = (int)($_POST['categoria_id'] ?? 0);
    $descricao_curta = trim($_POST['descricao_curta'] ?? '');
    $descricao     = trim($_POST['descricao'] ?? '');
    $preco_base    = (float)str_replace(',', '.', $_POST['preco_base'] ?? '0');
    $custo_base    = (float)str_replace(',', '.', $_POST['custo_base'] ?? '0');
    $prazo         = (int)($_POST['prazo_producao_dias'] ?? 7);
    $ativo         = isset($_POST['ativo']) ? 1 : 0;
    $destaque      = isset($_POST['destaque']) ? 1 : 0;
    $ordem         = (int)($_POST['ordem_exibicao'] ?? 0);

    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nome));
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE modelos SET categoria_id=:cat, nome=:nome, slug=:slug,
            descricao_curta=:dc, descricao=:desc, preco_base=:preco, custo_base=:custo,
            prazo_producao_dias=:prazo, ativo=:ativo, destaque=:destaque, ordem_exibicao=:ordem
            WHERE id=:id');
        $stmt->execute([
            ':cat'=>$categoria_id,':nome'=>$nome,':slug'=>$slug,
            ':dc'=>$descricao_curta,':desc'=>$descricao,':preco'=>$preco_base,
            ':custo'=>$custo_base,':prazo'=>$prazo,':ativo'=>$ativo,
            ':destaque'=>$destaque,':ordem'=>$ordem,':id'=>$id
        ]);

        // Upload de imagem ao editar também
        if (!empty($_FILES['imagem']['name'])) {
            $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed, true)) {
                $filename = $slug . '-' . time() . '.' . $ext;
                $dest = MODELOS_IMG_DIR . $filename;
                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
                    // Remove imagem principal anterior e insere nova
                    $pdo->prepare('UPDATE modelo_imagens SET principal=0 WHERE modelo_id=:id')
                        ->execute([':id'=>$id]);
                    $pdo->prepare('INSERT INTO modelo_imagens (modelo_id, arquivo, principal, ordem_exibicao) VALUES (:mid, :arq, 1, 1)')
                        ->execute([':mid'=>$id,':arq'=>$filename]);
                }
            }
        }

        $msg = 'Modelo atualizado!';
    } else {
        $stmt = $pdo->prepare('INSERT INTO modelos
            (categoria_id, nome, slug, descricao_curta, descricao, preco_base, custo_base,
             prazo_producao_dias, ativo, destaque, ordem_exibicao)
            VALUES (:cat,:nome,:slug,:dc,:desc,:preco,:custo,:prazo,:ativo,:destaque,:ordem)');
        $stmt->execute([
            ':cat'=>$categoria_id,':nome'=>$nome,':slug'=>$slug,
            ':dc'=>$descricao_curta,':desc'=>$descricao,':preco'=>$preco_base,
            ':custo'=>$custo_base,':prazo'=>$prazo,':ativo'=>$ativo,
            ':destaque'=>$destaque,':ordem'=>$ordem
        ]);
        $novoId = (int)$pdo->lastInsertId();

        // Upload de imagem
        if (!empty($_FILES['imagem']['name'])) {
            $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed, true)) {
                $filename = $slug . '-' . time() . '.' . $ext;
                $dest = MODELOS_IMG_DIR . $filename;
                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
                    $pdo->prepare('INSERT INTO modelo_imagens (modelo_id, arquivo, principal, ordem_exibicao) VALUES (:mid, :arq, 1, 1)')
                        ->execute([':mid'=>$novoId,':arq'=>$filename]);
                }
            }
        }
        $msg = 'Modelo criado com sucesso!';
    }
}

// Excluir
if (isset($_GET['excluir'])) {
    $pdo->prepare('DELETE FROM modelos WHERE id=:id')->execute([':id'=>(int)$_GET['excluir']]);
    $msg = 'Modelo removido.';
}

// Editar
$editando = null;
if (isset($_GET['editar'])) {
    $s = $pdo->prepare('SELECT * FROM modelos WHERE id=:id');
    $s->execute([':id'=>(int)$_GET['editar']]);
    $editando = $s->fetch();
}

$modelos = $pdo->query(
    'SELECT m.*, c.nome AS categoria_nome,
     (SELECT arquivo FROM modelo_imagens mi WHERE mi.modelo_id=m.id ORDER BY mi.principal DESC LIMIT 1) AS imagem
     FROM modelos m LEFT JOIN categorias c ON c.id=m.categoria_id
     ORDER BY m.ordem_exibicao ASC, m.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modelos — Admin DS 3D</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
<?php require __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
    <header class="admin-topbar">
        <h2>Modelos</h2>
    </header>
    <div class="admin-content">
        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

        <div class="card">
            <h3><?= $editando ? 'Editar modelo' : 'Novo modelo' ?></h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id" value="<?= (int)($editando['id'] ?? 0) ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Nome do modelo *</label>
                        <input type="text" name="nome" required
                               value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Slug <small>(gerado automaticamente)</small></label>
                        <input type="text" name="slug"
                               value="<?= htmlspecialchars($editando['slug'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Categoria *</label>
                        <select name="categoria_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?= ($editando['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Preço base (R$) *</label>
                        <input type="text" name="preco_base" required
                               value="<?= htmlspecialchars((string)($editando['preco_base'] ?? '')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Custo base (R$)</label>
                        <input type="text" name="custo_base"
                               value="<?= htmlspecialchars((string)($editando['custo_base'] ?? '')) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição curta <small>(aparece nos cards)</small></label>
                    <input type="text" name="descricao_curta" maxlength="160"
                           value="<?= htmlspecialchars($editando['descricao_curta'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Descrição completa</label>
                    <textarea name="descricao" rows="4"><?= htmlspecialchars($editando['descricao'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Prazo de produção (dias)</label>
                        <input type="number" name="prazo_producao_dias" min="1"
                               value="<?= (int)($editando['prazo_producao_dias'] ?? 7) ?>">
                    </div>
                    <div class="form-group">
                        <label>Ordem de exibição</label>
                        <input type="number" name="ordem_exibicao" min="0"
                               value="<?= (int)($editando['ordem_exibicao'] ?? 0) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Imagem principal</label>
                    <?php if ($editando): ?>
                        <?php
                        $imgAtual = $pdo->prepare('SELECT arquivo FROM modelo_imagens WHERE modelo_id=:id AND principal=1 LIMIT 1');
                        $imgAtual->execute([':id'=>$editando['id']]);
                        $imgAtual = $imgAtual->fetchColumn();
                        ?>
                        <?php if ($imgAtual): ?>
                            <div style="margin-bottom:8px">
                                <img src="<?= MODELOS_IMG_URL . htmlspecialchars($imgAtual) ?>"
                                     style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #ddd" alt="">
                                <small style="display:block;color:#888;margin-top:4px">Imagem atual</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagem" accept="image/*">
                        <small>Envie uma nova imagem para substituir a atual. JPG, PNG ou WebP.</small>
                    <?php else: ?>
                        <input type="file" name="imagem" accept="image/*">
                        <small>JPG, PNG ou WebP. Salva em <code>assets/img/modelos/</code></small>
                    <?php endif; ?>
                </div>

                <div class="form-row form-checks">
                    <div class="form-check">
                        <input type="checkbox" id="ativo" name="ativo" value="1"
                               <?= ($editando['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label for="ativo">Ativo</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="destaque" name="destaque" value="1"
                               <?= ($editando['destaque'] ?? 0) ? 'checked' : '' ?>>
                        <label for="destaque">Em destaque</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Salvar modelo</button>
                    <?php if ($editando): ?>
                        <a href="/admin/modelos.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Modelos cadastrados</h3>
            <?php if (empty($modelos)): ?>
                <p class="empty-msg">Nenhum modelo ainda. Crie o primeiro acima!</p>
            <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Img</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Destaque</th><th>Ativo</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($modelos as $m): ?>
                <tr>
                    <td>
                        <?php if ($m['imagem']): ?>
                            <img src="<?= MODELOS_IMG_URL . htmlspecialchars($m['imagem']) ?>" class="thumb" alt="">
                        <?php else: ?>
                            <span class="no-img">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($m['nome']) ?></td>
                    <td><?= htmlspecialchars($m['categoria_nome'] ?? '—') ?></td>
                    <td>R$ <?= number_format((float)$m['preco_base'], 2, ',', '.') ?></td>
                    <td><?= $m['destaque'] ? '<span class="badge badge-gold">Sim</span>' : '—' ?></td>
                    <td><?= $m['ativo'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Não</span>' ?></td>
                    <td class="actions">
                        <a href="?editar=<?= (int)$m['id'] ?>">Editar</a>
                        <a href="?excluir=<?= (int)$m['id'] ?>" class="danger"
                           onclick="return confirm('Excluir <?= htmlspecialchars(addslashes($m['nome'])) ?>?')">Excluir</a>
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
