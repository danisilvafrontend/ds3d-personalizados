<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /admin/index.php');
        exit;
    }
}

function adminLogin(PDO $pdo, string $email, string $senha): bool
{
    $stmt = $pdo->prepare('SELECT id, nome, senha_hash FROM admin_usuarios WHERE email = :email AND ativo = 1 LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha_hash'])) {
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_nome'] = $user['nome'];
        return true;
    }
    return false;
}
