<?php
declare(strict_types=1);
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (adminLogin($pdo, $email, $senha)) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    $erro = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — DS 3D Personalizados</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-logo">
        <img src="/assets/img/icone.svg" alt="DS" width="48" height="48">
        <span>DS 3D <strong>Admin</strong></span>
    </div>
    <h1>Entrar no painel</h1>
    <?php if ($erro): ?>
        <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post" class="login-form">
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required autofocus
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Entrar</button>
    </form>
</div>
</body>
</html>
