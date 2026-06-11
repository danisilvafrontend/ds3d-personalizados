<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';

$pageTitle = 'DS 3D Personalizados | Peças 3D personalizadas';
$config = getSiteConfig($pdo);
$modelos = getFeaturedModels($pdo, 6);
$tipos = getCustomizationTypes($pdo);

require __DIR__ . '/includes/header.php';
?>
<section class="hero-section">
    <div class="hero-bg"></div>
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Peças sob encomenda</span>
            <h1>Peças 3D personalizadas com acabamento artesanal.</h1>
            <p>Escolha um modelo, personalize cores e detalhes, veja uma estimativa e envie seu pedido direto pelo site.</p>
            <div class="hero-actions">
                <a href="#modelos" class="btn btn-primary">Ver modelos</a>
                <a href="#simulador" class="btn btn-secondary hero-btn-outline">Simular valor</a>
            </div>
            <ul class="hero-benefits">
                <li>Modelos personalizados</li>
                <li>Produção sob encomenda</li>
                <li>Atendimento direto</li>
            </ul>
        </div>
        <div class="hero-product-wrap">
            <div class="hero-product-img hero-product-video">
                <video
                    src="/assets/img/impressao.mp4"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="metadata"
                ></video>
            </div>
            <div class="hero-badge">
                <p class="mini-label">Estimativas a partir de</p>
                <strong><?= formatMoney(21.36) ?></strong>
            </div>
        </div>
    </div>
</section>

<section class="section" id="como-funciona">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Como funciona</span>
            <h2>Um processo simples para pedir sua peça.</h2>
        </div>
        <div class="steps-grid">
            <article class="step-card"><span>1</span><h3>Escolha o modelo</h3><p>Selecione a peça base que mais combina com o seu pedido.</p></article>
            <article class="step-card"><span>2</span><h3>Personalize</h3><p>Defina cor, símbolo, nome gravado ou outros detalhes disponíveis.</p></article>
            <article class="step-card"><span>3</span><h3>Veja a estimativa</h3><p>O sistema calcula um valor inicial com base nas escolhas.</p></article>
            <article class="step-card"><span>4</span><h3>Envie o pedido</h3><p>Finalize via formulário ou WhatsApp para atendimento direto.</p></article>
        </div>
    </div>
</section>

<section class="section section-alt" id="modelos">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Modelos</span>
            <h2>Modelos em destaque</h2>
        </div>
        <div class="models-grid">
            <?php foreach ($modelos as $modelo): ?>
                <article class="model-card">
                    <div class="model-thumb">
                        <?php if (!empty($modelo['imagem_principal'])): ?>
                            <img
                                src="/assets/img/modelos/<?= htmlspecialchars($modelo['imagem_principal']) ?>"
                                alt="<?= htmlspecialchars($modelo['nome']) ?>"
                                loading="lazy"
                                width="400"
                                height="400"
                            >
                        <?php else: ?>
                            <span class="model-thumb-placeholder">Foto em breve</span>
                        <?php endif; ?>
                    </div>
                    <div class="model-content">
                        <p class="card-tag"><?= htmlspecialchars($modelo['categoria_nome'] ?? 'Modelo') ?></p>
                        <h3><?= htmlspecialchars($modelo['nome']) ?></h3>
                        <p><?= htmlspecialchars($modelo['descricao_curta'] ?? 'Peça personalizada sob encomenda.') ?></p>
                        <div class="model-footer">
                            <strong><?= formatMoney((float)$modelo['preco_base']) ?></strong>
                            <a href="#simulador" class="text-link" data-model-id="<?= (int)$modelo['id'] ?>">Quero este modelo</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="simulador">
    <div class="container simulator-grid">
        <div>
            <div class="section-heading">
                <span class="eyebrow">Simulador</span>
                <h2>Monte uma estimativa inicial da sua peça.</h2>
            </div>
            <p>Selecione o modelo e as personalizações disponíveis. O valor exibido é uma referência inicial para agilizar seu atendimento.</p>
        </div>
        <form class="simulator-card" id="simulatorForm">
            <div class="form-group">
                <label for="modelo_id">Modelo</label>
                <select name="modelo_id" id="modelo_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($modelos as $modelo): ?>
                        <option value="<?= (int)$modelo['id'] ?>" data-preco="<?= htmlspecialchars((string)$modelo['preco_base']) ?>">
                            <?= htmlspecialchars($modelo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($tipos as $tipo): ?>
                <?php if (in_array($tipo['slug'], ['cor', 'simbolo'], true)): ?>
                    <div class="form-group">
                        <label for="tipo_<?= (int)$tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?></label>
                        <select name="custom[<?= (int)$tipo['id'] ?>]" id="tipo_<?= (int)$tipo['id'] ?>">
                            <option value="">Selecione</option>
                            <?php foreach (getCustomizationOptions($pdo, (int)$tipo['id']) as $opcao): ?>
                                <option value="<?= (int)$opcao['id'] ?>" data-valor="<?= htmlspecialchars((string)$opcao['valor_adicional']) ?>">
                                    <?= htmlspecialchars($opcao['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="form-group">
                <label for="nome_gravado">Nome gravado</label>
                <input type="text" id="nome_gravado" name="nome_gravado" placeholder="Opcional">
                <small>Adicional sugerido será aplicado quando preenchido.</small>
            </div>

            <div class="form-group">
                <label for="quantidade">Quantidade</label>
                <input type="number" id="quantidade" name="quantidade" min="1" value="1">
            </div>

            <div class="estimate-box">
                <span>Valor estimado</span>
                <strong id="estimatedValue">R$ 0,00</strong>
                <p>Valor sujeito à confirmação após análise final.</p>
            </div>

            <div class="simulator-actions">
                <button type="button" class="btn btn-primary" id="btnEstimate">Atualizar estimativa</button>
                <a href="#contato" class="btn btn-secondary">Solicitar atendimento</a>
            </div>
        </form>
    </div>
</section>

<section class="section section-alt" id="contato-formulario">
    <div class="container contact-grid">
        <div>
            <div class="section-heading">
                <span class="eyebrow">Contato</span>
                <h2>Envie seu pedido ou tire dúvidas.</h2>
            </div>
            <p>Se preferir, você pode enviar a referência do modelo e os detalhes pelo formulário abaixo.</p>
        </div>
        <form class="contact-card" method="post" action="ajax/salvar_orcamento.php">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="whatsapp">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="mensagem">Observações</label>
                <textarea id="mensagem" name="mensagem" rows="4"></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Enviar solicitação</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
