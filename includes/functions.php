<?php

declare(strict_types=1);

function getSiteConfig(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT chave_nome, valor_texto FROM configuracoes_sistema');
    $configs = [];
    foreach ($stmt->fetchAll() as $row) {
        $configs[$row['chave_nome']] = $row['valor_texto'];
    }
    return $configs;
}

function getFeaturedModels(PDO $pdo, int $limit = 6): array
{
    $sql = "SELECT m.*, c.nome AS categoria_nome,
                   (SELECT arquivo FROM modelo_imagens mi WHERE mi.modelo_id = m.id ORDER BY mi.principal DESC, mi.ordem_exibicao ASC LIMIT 1) AS imagem_principal
            FROM modelos m
            LEFT JOIN categorias c ON c.id = m.categoria_id
            WHERE m.ativo = 1
            ORDER BY m.destaque DESC, m.ordem_exibicao ASC, m.id DESC
            LIMIT :limite";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getCustomizationTypes(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM personalizacao_tipos WHERE ativo = 1 ORDER BY ordem_exibicao ASC, id ASC");
    return $stmt->fetchAll();
}

function getCustomizationOptions(PDO $pdo, int $typeId): array
{
    $stmt = $pdo->prepare("SELECT * FROM personalizacao_opcoes WHERE personalizacao_tipo_id = :tipo AND ativo = 1 ORDER BY ordem_exibicao ASC, id ASC");
    $stmt->execute([':tipo' => $typeId]);
    return $stmt->fetchAll();
}

function formatMoney(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}
