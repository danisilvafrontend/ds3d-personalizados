<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$modeloId = (int)($_POST['modelo_id'] ?? 0);
$quantidade = max(1, (int)($_POST['quantidade'] ?? 1));
$nomeGravado = trim((string)($_POST['nome_gravado'] ?? ''));
$customizacoes = $_POST['custom'] ?? [];

if ($modeloId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Modelo inválido.']);
    exit;
}

$stmt = $pdo->prepare('SELECT preco_base FROM modelos WHERE id = :id AND ativo = 1');
$stmt->execute([':id' => $modeloId]);
$modelo = $stmt->fetch();

if (!$modelo) {
    echo json_encode(['success' => false, 'message' => 'Modelo não encontrado.']);
    exit;
}

$base = (float)$modelo['preco_base'];
$adicionais = 0.0;

foreach ($customizacoes as $opcaoId) {
    $opcaoId = (int)$opcaoId;
    if ($opcaoId > 0) {
        $stmt = $pdo->prepare('SELECT valor_adicional FROM personalizacao_opcoes WHERE id = :id AND ativo = 1');
        $stmt->execute([':id' => $opcaoId]);
        $opcao = $stmt->fetch();
        if ($opcao) {
            $adicionais += (float)$opcao['valor_adicional'];
        }
    }
}

if ($nomeGravado !== '') {
    $adicionais += 2.00;
}

$total = ($base + $adicionais) * $quantidade;

echo json_encode([
    'success' => true,
    'valor' => number_format($total, 2, ',', '.'),
    'valor_numerico' => $total,
]);
