<?php
/**
 * SETUP ÚNICO — cria o usuário admin
 * Acesse UMA VEZ: seusite.com/admin/setup.php
 * APAGUE este arquivo após criar o usuário!
 */
declare(strict_types=1);
require __DIR__ . '/../config.php';

// ─── CONFIGURE AQUI ───────────────────────────────────────────
$NOME  = 'Dani Silva';
$EMAIL = 'danisilvafrontend@gmail.com'; // troque pelo seu e-mail real
$SENHA = 'DS3d@2026';                   // troque para uma senha forte
// ──────────────────────────────────────────────────────────────

try {
    // Cria a tabela se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        senha_hash VARCHAR(255) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Verifica se já existe
    $existe = $pdo->prepare('SELECT id FROM admin_usuarios WHERE email = :email');
    $existe->execute([':email' => $EMAIL]);

    if ($existe->fetch()) {
        echo '<p style="font-family:sans-serif;color:orange">⚠️ Usuário com este e-mail já existe. Nenhuma alteração feita.</p>';
    } else {
        $hash = password_hash($SENHA, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO admin_usuarios (nome, email, senha_hash) VALUES (:nome, :email, :hash)')
            ->execute([':nome'=>$NOME, ':email'=>$EMAIL, ':hash'=>$hash]);
        echo '<p style="font-family:sans-serif;color:green">✅ Usuário <strong>' . htmlspecialchars($NOME) . '</strong> criado com sucesso!<br>';
        echo 'E-mail: <strong>' . htmlspecialchars($EMAIL) . '</strong><br>';
        echo '<strong style="color:red">⚠️ APAGUE este arquivo agora! (admin/setup.php)</strong></p>';
    }
} catch (Exception $e) {
    echo '<p style="color:red">Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
