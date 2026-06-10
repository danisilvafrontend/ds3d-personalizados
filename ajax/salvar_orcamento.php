<?php
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$nome = trim((string)($_POST['nome'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$whatsapp = trim((string)($_POST['whatsapp'] ?? ''));
$mensagem = trim((string)($_POST['mensagem'] ?? ''));

if ($nome === '' || $whatsapp === '') {
    header('Location: ../index.php?erro=1#contato');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO leads (nome, email, whatsapp, observacoes, origem) VALUES (:nome, :email, :whatsapp, :obs, :origem)');
$stmt->execute([
    ':nome' => $nome,
    ':email' => $email !== '' ? $email : null,
    ':whatsapp' => $whatsapp,
    ':obs' => $mensagem !== '' ? $mensagem : null,
    ':origem' => 'landing_page',
]);

header('Location: ../index.php?sucesso=1#contato');
exit;
