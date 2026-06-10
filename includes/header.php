<?php
if (!isset($pageTitle)) {
    $pageTitle = 'DS 3D Personalizados';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Peças 3D personalizadas feitas sob encomenda. Escolha o modelo, personalize e solicite seu orçamento.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <div class="brand">
            <a href="index.php" class="brand-link">
                <img
                    src="assets/img/logo3d-personalizados.jpg"
                    alt="DS 3D Personalizados"
                    class="brand-logo"
                    width="72"
                    height="auto"
                    loading="eager"
                >
            </a>
        </div>
        <nav class="main-nav">
            <a href="#modelos">Modelos</a>
            <a href="#como-funciona">Como funciona</a>
            <a href="#simulador">Simular valor</a>
            <a href="#contato">Contato</a>
        </nav>
        <a class="btn btn-primary" href="#simulador">Solicitar orçamento</a>
    </div>
</header>
<main>
