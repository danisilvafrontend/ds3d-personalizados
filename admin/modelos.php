<?php
declare(strict_types=1);
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/auth.php';
requireLogin();

$msg     = '';
$msgType = 'success';
$categorias = $pdo->query('SELECT id, nome FROM categorias WHERE ativo=1 ORDER BY nome')->fetchAll();

define('MODELOS_IMG_DIR', __DIR__ . '/../assets/img/modelos/');
define('MODELOS_IMG_URL', '/assets/img/modelos/');

if (!is_dir(MODELOS_IMG_DIR)) {
    mkdir(MODELOS_IMG_DIR, 0755, true);
}

/* ── helpers ── */
function uploadImagem(string $tmpName, string $slug, string $ext): string|false {
    $filename = $slug . '-' . time() . '-' . mt_rand(100,999) . '.' . $ext;
    $dest     = MODELOS_IMG_DIR . $filename;
    return move_uploaded_file($tmpName, $dest) ? $filename : false;
}

$allowed = ['jpg','jpeg','png','webp'];

/* ════════════════════════════════════════
   AÇÃO: Salvar / criar modelo
════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $id             = (int)($_POST['id'] ?? 0);
    $nome           = trim($_POST['nome'] ?? '');
    $slug           = trim($_POST['slug'] ?? '');
    $categoria_id   = (int)($_POST['categoria_id'] ?? 0);
    $descricao_curta= trim($_POST['descricao_curta'] ?? '');
    $descricao      = trim($_POST['descricao'] ?? '');
    $preco_base     = (float)str_replace(',', '.', $_POST['preco_base'] ?? '0');
    $custo_base     = (float)str_replace(',', '.', $_POST['custo_base'] ?? '0');
    $prazo          = (int)($_POST['prazo_producao_dias'] ?? 7);
    $ativo          = isset($_POST['ativo'])    ? 1 : 0;
    $destaque       = isset($_POST['destaque']) ? 1 : 0;
    $ordem          = (int)($_POST['ordem_exibicao'] ?? 0);

    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nome));
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE modelos SET categoria_id=:cat, nome=:nome, slug=:slug,
            descricao_curta=:dc, descricao=:desc, preco_base=:preco, custo_base=:custo,
            prazo_producao_dias=:prazo, ativo=:ativo, destaque=:destaque, ordem_exibicao=:ordem
            WHERE id=:id')->execute([
                ':cat'=>$categoria_id,':nome'=>$nome,':slug'=>$slug,
                ':dc'=>$descricao_curta,':desc'=>$descricao,':preco'=>$preco_base,
                ':custo'=>$custo_base,':prazo'=>$prazo,':ativo'=>$ativo,
                ':destaque'=>$destaque,':ordem'=>$ordem,':id'=>$id
        ]);
        $msg = 'Modelo atualizado!';
    } else {
        $pdo->prepare('INSERT INTO modelos
            (categoria_id, nome, slug, descricao_curta, descricao, preco_base, custo_base,
             prazo_producao_dias, ativo, destaque, ordem_exibicao)
            VALUES (:cat,:nome,:slug,:dc,:desc,:preco,:custo,:prazo,:ativo,:destaque,:ordem)')
        ->execute([
            ':cat'=>$categoria_id,':nome'=>$nome,':slug'=>$slug,
            ':dc'=>$descricao_curta,':desc'=>$descricao,':preco'=>$preco_base,
            ':custo'=>$custo_base,':prazo'=>$prazo,':ativo'=>$ativo,
            ':destaque'=>$destaque,':ordem'=>$ordem
        ]);
        $id  = (int)$pdo->lastInsertId();
        $msg = 'Modelo criado com sucesso!';
    }

    /* Upload de imagem principal única (campo legado) */
    if (!empty($_FILES['imagem']['name'])) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed, true)) {
            $filename = uploadImagem($_FILES['imagem']['tmp_name'], $slug, $ext);
            if ($filename) {
                $pdo->prepare('UPDATE modelo_imagens SET principal=0 WHERE modelo_id=:id')->execute([':id'=>$id]);
                $pdo->prepare('INSERT INTO modelo_imagens (modelo_id, arquivo, principal, ordem_exibicao) VALUES (:mid,:arq,1,1)')
                    ->execute([':mid'=>$id,':arq'=>$filename]);
            }
        }
    }

    /* Upload de múltiplas imagens da galeria */
    if (!empty($_FILES['galeria']['name'][0])) {
        $totalGaleria = count($_FILES['galeria']['name']);
        /* Ordem atual máxima */
        $maxOrdem = (int)$pdo->prepare('SELECT COALESCE(MAX(ordem_exibicao),0) FROM modelo_imagens WHERE modelo_id=:id')
                              ->execute([':id'=>$id]) ? $pdo->query("SELECT COALESCE(MAX(ordem_exibicao),0) FROM modelo_imagens WHERE modelo_id={$id}")->fetchColumn() : 0;

        /* Verifica se já existe alguma imagem principal */
        $temPrincipal = (int)$pdo->query("SELECT COUNT(*) FROM modelo_imagens WHERE modelo_id={$id} AND principal=1")->fetchColumn();

        for ($i = 0; $i < $totalGaleria; $i++) {
            if ($_FILES['galeria']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['galeria']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;

            $filename = uploadImagem($_FILES['galeria']['tmp_name'][$i], $slug, $ext);
            if ($filename) {
                $maxOrdem++;
                $isPrincipal = (!$temPrincipal && $i === 0) ? 1 : 0;
                if ($isPrincipal) $temPrincipal = 1;
                $pdo->prepare('INSERT INTO modelo_imagens (modelo_id, arquivo, principal, ordem_exibicao) VALUES (:mid,:arq,:pri,:ord)')
                    ->execute([':mid'=>$id,':arq'=>$filename,':pri'=>$isPrincipal,':ord'=>$maxOrdem]);
            }
        }
    }

    header('Location: /admin/modelos.php?editado=' . $id . '&msg=' . urlencode($msg));
    exit;
}

/* ════════════════════════════════════════
   AÇÃO: Excluir imagem da galeria
════════════════════════════════════════ */
if (isset($_GET['del_img'])) {
    $imgId    = (int)$_GET['del_img'];
    $modeloId = (int)($_GET['modelo_id'] ?? 0);
    $row = $pdo->prepare('SELECT arquivo, principal FROM modelo_imagens WHERE id=:id AND modelo_id=:mid');
    $row->execute([':id'=>$imgId,':mid'=>$modeloId]);
    $row = $row->fetch();
    if ($row) {
        $file = MODELOS_IMG_DIR . $row['arquivo'];
        if (file_exists($file)) unlink($file);
        $pdo->prepare('DELETE FROM modelo_imagens WHERE id=:id')->execute([':id'=>$imgId]);
        /* Se era principal, promove a próxima */
        if ($row['principal']) {
            $prox = $pdo->prepare('SELECT id FROM modelo_imagens WHERE modelo_id=:mid ORDER BY ordem_exibicao LIMIT 1');
            $prox->execute([':mid'=>$modeloId]);
            $prox = $prox->fetchColumn();
            if ($prox) {
                $pdo->prepare('UPDATE modelo_imagens SET principal=1 WHERE id=:id')->execute([':id'=>$prox]);
            }
        }
    }
    header('Location: /admin/modelos.php?editar=' . $modeloId . '&msg=' . urlencode('Imagem removida.'));
    exit;
}

/* ════════════════════════════════════════
   AÇÃO: Definir imagem como principal
════════════════════════════════════════ */
if (isset($_GET['set_principal'])) {
    $imgId    = (int)$_GET['set_principal'];
    $modeloId = (int)($_GET['modelo_id'] ?? 0);
    $pdo->prepare('UPDATE modelo_imagens SET principal=0 WHERE modelo_id=:mid')->execute([':mid'=>$modeloId]);
    $pdo->prepare('UPDATE modelo_imagens SET principal=1 WHERE id=:id')->execute([':id'=>$imgId]);
    header('Location: /admin/modelos.php?editar=' . $modeloId . '&msg=' . urlencode('Imagem principal atualizada.'));
    exit;
}

/* ════════════════════════════════════════
   AÇÃO: Excluir modelo
════════════════════════════════════════ */
if (isset($_GET['excluir'])) {
    $delId = (int)$_GET['excluir'];
    /* Remove arquivos físicos */
    $imgs = $pdo->prepare('SELECT arquivo FROM modelo_imagens WHERE modelo_id=:id');
    $imgs->execute([':id'=>$delId]);
    foreach ($imgs->fetchAll() as $img) {
        $f = MODELOS_IMG_DIR . $img['arquivo'];
        if (file_exists($f)) unlink($f);
    }
    $pdo->prepare('DELETE FROM modelo_imagens WHERE modelo_id=:id')->execute([':id'=>$delId]);
    $pdo->prepare('DELETE FROM modelos WHERE id=:id')->execute([':id'=>$delId]);
    header('Location: /admin/modelos.php?msg=' . urlencode('Modelo removido.'));
    exit;
}

/* ── Mensagem via GET (após redirect) ── */
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

/* ── Editar ── */
$editando = null;
$galeriaAtual = [];
if (isset($_GET['editar']) || isset($_GET['editado'])) {
    $editId = (int)($_GET['editar'] ?? $_GET['editado'] ?? 0);
    $s = $pdo->prepare('SELECT * FROM modelos WHERE id=:id');
    $s->execute([':id'=>$editId]);
    $editando = $s->fetch();
    if ($editando) {
        $g = $pdo->prepare('SELECT * FROM modelo_imagens WHERE modelo_id=:id ORDER BY principal DESC, ordem_exibicao ASC');
        $g->execute([':id'=>$editId]);
        $galeriaAtual = $g->fetchAll();
    }
}

$modelos = $pdo->query(
    'SELECT m.*, c.nome AS categoria_nome,
     (SELECT arquivo FROM modelo_imagens mi WHERE mi.modelo_id=m.id AND mi.principal=1 LIMIT 1) AS imagem
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
<style>
/* ── Galeria de imagens ── */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 12px;
    margin: 12px 0 20px;
}
.gallery-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid transparent;
    background: #f3ede8;
    aspect-ratio: 1;
}
.gallery-item.is-principal {
    border-color: #6e2132;
}
.gallery-item img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}
.gallery-item-badge {
    position: absolute; top: 5px; left: 5px;
    background: #6e2132; color: #fff;
    font-size: .65rem; font-weight: 700;
    padding: 2px 6px; border-radius: 99px;
    text-transform: uppercase; letter-spacing: .05em;
}
.gallery-item-actions {
    position: absolute; bottom: 0; left: 0; right: 0;
    display: flex; gap: 4px; padding: 6px;
    background: linear-gradient(transparent, rgba(0,0,0,.55));
    opacity: 0; transition: opacity .18s;
}
.gallery-item:hover .gallery-item-actions { opacity: 1; }
.gallery-item-actions a {
    flex: 1; text-align: center;
    font-size: .7rem; font-weight: 600;
    padding: 3px 0; border-radius: 6px;
    text-decoration: none;
}
.gallery-btn-principal { background: #fff; color: #6e2132; }
.gallery-btn-excluir   { background: #e53e3e; color: #fff; }
.gallery-empty {
    color: #aaa; font-size: .88rem;
    padding: 12px 0;
}
.upload-area {
    border: 2px dashed #d9cfc8;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    background: #fdf9f7;
}
.upload-area:hover { border-color: #6e2132; background: #fdf5f5; }
.upload-area input[type=file] { display: none; }
.upload-area-label {
    display: flex; flex-direction: column;
    align-items: center; gap: 6px; cursor: pointer;
}
.upload-area-icon { font-size: 2rem; }
.upload-area-text { font-size: .88rem; color: #888; }
.upload-area-text strong { color: #6e2132; }
#previewLista {
    display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;
}
#previewLista img {
    width: 64px; height: 64px;
    object-fit: cover; border-radius: 8px;
    border: 1px solid #ddd;
}
</style>
</head>
<body>
<?php require __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
    <header class="admin-topbar">
        <h2>Modelos</h2>
    </header>
    <div class="admin-content">
        <?php if ($msg): ?>
            <div class="alert alert-success"><?= $msg ?></div>
        <?php endif; ?>

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

                <?php if ($editando && !empty($galeriaAtual)): ?>
                <!-- ── Galeria atual ── -->
                <div class="form-group">
                    <label>Galeria atual (<?= count($galeriaAtual) ?> imagem<?= count($galeriaAtual) > 1 ? 'ns' : '' ?>)</label>
                    <div class="gallery-grid">
                        <?php foreach ($galeriaAtual as $img): ?>
                            <div class="gallery-item <?= $img['principal'] ? 'is-principal' : '' ?>">
                                <img src="<?= MODELOS_IMG_URL . htmlspecialchars($img['arquivo']) ?>" alt="">
                                <?php if ($img['principal']): ?>
                                    <span class="gallery-item-badge">Principal</span>
                                <?php endif; ?>
                                <div class="gallery-item-actions">
                                    <?php if (!$img['principal']): ?>
                                        <a href="?set_principal=<?= (int)$img['id'] ?>&modelo_id=<?= (int)$editando['id'] ?>"
                                           class="gallery-btn-principal"
                                           title="Definir como principal">&#9733;</a>
                                    <?php endif; ?>
                                    <a href="?del_img=<?= (int)$img['id'] ?>&modelo_id=<?= (int)$editando['id'] ?>"
                                       class="gallery-btn-excluir"
                                       onclick="return confirm('Remover esta imagem?')"
                                       title="Excluir imagem">&#10005;</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small style="color:#888">Passe o mouse sobre a imagem para ver as ações. A imagem com borda vermelha é a principal.</small>
                </div>
                <?php elseif ($editando): ?>
                    <p class="gallery-empty">Nenhuma imagem cadastrada ainda para este modelo.</p>
                <?php endif; ?>

                <!-- ── Upload galeria ── -->
                <div class="form-group">
                    <label><?= $editando ? 'Adicionar imagens à galeria' : 'Imagens do modelo' ?></label>
                    <div class="upload-area" id="uploadArea">
                        <label class="upload-area-label" for="galeriaInput">
                            <span class="upload-area-icon">🖼️</span>
                            <span class="upload-area-text">
                                <strong>Clique para selecionar</strong> ou arraste as imagens aqui<br>
                                JPG, PNG ou WebP · Você pode selecionar várias de uma vez
                            </span>
                        </label>
                        <input type="file" id="galeriaInput" name="galeria[]"
                               accept="image/*" multiple>
                    </div>
                    <div id="previewLista"></div>
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

        <!-- ── Lista de modelos ── -->
        <div class="card">
            <h3>Modelos cadastrados</h3>
            <?php if (empty($modelos)): ?>
                <p class="empty-msg">Nenhum modelo ainda. Crie o primeiro acima!</p>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr><th>Img</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Destaque</th><th>Ativo</th><th>Ações</th></tr>
                </thead>
                <tbody>
                <?php foreach ($modelos as $m): ?>
                <tr>
                    <td>
                        <?php if ($m['imagem']): ?>
                            <img src="<?= MODELOS_IMG_URL . htmlspecialchars($m['imagem']) ?>"
                                 class="thumb" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                        <?php else: ?>
                            <span class="no-img" style="color:#ccc;font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($m['nome']) ?></td>
                    <td><?= htmlspecialchars($m['categoria_nome'] ?? '—') ?></td>
                    <td>R$ <?= number_format((float)$m['preco_base'], 2, ',', '.') ?></td>
                    <td><?= $m['destaque'] ? '<span class="badge badge-gold">Sim</span>' : '—' ?></td>
                    <td><?= $m['ativo']    ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Não</span>' ?></td>
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

<script>
(function () {
    var input   = document.getElementById('galeriaInput');
    var area    = document.getElementById('uploadArea');
    var preview = document.getElementById('previewLista');

    if (!input || !area || !preview) return;

    /* Clique na área abre o seletor */
    area.addEventListener('click', function (e) {
        if (e.target !== input) input.click();
    });

    /* Drag & drop */
    area.addEventListener('dragover', function (e) {
        e.preventDefault();
        area.style.borderColor = '#6e2132';
    });
    area.addEventListener('dragleave', function () {
        area.style.borderColor = '#d9cfc8';
    });
    area.addEventListener('drop', function (e) {
        e.preventDefault();
        area.style.borderColor = '#d9cfc8';
        var dt = e.dataTransfer;
        if (dt && dt.files.length) {
            input.files = dt.files;
            mostrarPreview(dt.files);
        }
    });

    input.addEventListener('change', function () {
        mostrarPreview(this.files);
    });

    function mostrarPreview(files) {
        preview.innerHTML = '';
        if (!files || !files.length) return;
        Array.from(files).forEach(function (file) {
            if (!file.type.startsWith('image/')) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.title = file.name;
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
})();
</script>
</body>
</html>
