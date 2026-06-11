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
                <video src="/assets/img/impressao.mp4" autoplay muted loop playsinline preload="metadata"></video>
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
                <?php
                $imgStyle = !empty($modelo['imagem_principal'])
                    ? 'background-image:url(/assets/img/modelos/' . htmlspecialchars($modelo['imagem_principal']) . ')'
                    : '';
                $modeloJson = htmlspecialchars(json_encode([
                    'id'         => (int)$modelo['id'],
                    'nome'       => $modelo['nome'],
                    'categoria'  => $modelo['categoria_nome'] ?? 'Modelo',
                    'descricao'  => $modelo['descricao'] ?? $modelo['descricao_curta'] ?? '',
                    'preco_base' => (float)$modelo['preco_base'],
                    'imagem'     => $modelo['imagem_principal'] ?? '',
                ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                ?>
                <article class="model-card">
                    <div class="model-thumb" style="<?= $imgStyle ?>">
                        <?php if (empty($modelo['imagem_principal'])): ?>
                            <span class="model-thumb-placeholder">Foto em breve</span>
                        <?php endif; ?>
                    </div>
                    <div class="model-content">
                        <p class="card-tag"><?= htmlspecialchars($modelo['categoria_nome'] ?? 'Modelo') ?></p>
                        <h3><?= htmlspecialchars($modelo['nome']) ?></h3>
                        <p class="model-desc"><?= htmlspecialchars($modelo['descricao_curta'] ?? 'Peça personalizada sob encomenda.') ?></p>
                        <div class="model-footer">
                            <div class="model-price">
                                <span class="price-label">A partir de</span>
                                <strong><?= formatMoney((float)$modelo['preco_base']) ?></strong>
                            </div>
                            <div class="model-actions">
                                <a href="#simulador" class="btn btn-primary btn-sm" data-model-id="<?= (int)$modelo['id'] ?>">Quero esse modelo</a>
                                <button type="button" class="btn btn-outline btn-sm" data-modal='<?= $modeloJson ?>'>Detalhes</button>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Modal de detalhes do modelo -->
<div id="modeloModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalNome" hidden>
    <div class="modal-box">
        <button class="modal-close" id="modalCloseBtn" aria-label="Fechar">&times;</button>
        <div class="modal-inner">
            <div class="modal-img-wrap">
                <div class="modal-img" id="modalImg"></div>
            </div>
            <div class="modal-body">
                <p class="card-tag" id="modalCategoria"></p>
                <h2 id="modalNome"></h2>
                <p id="modalDesc" class="modal-desc-text"></p>

                <div class="discount-table">
                    <h4>Desconto progressivo por quantidade</h4>
                    <table>
                        <thead>
                            <tr><th>Faixa</th><th>Qtde</th><th>Desconto</th><th>Preço/un</th></tr>
                        </thead>
                        <tbody id="discountTableBody"></tbody>
                    </table>
                </div>

                <div class="modal-simulator">
                    <h4>Simule seu pedido</h4>
                    <div class="modal-sim-row">
                        <label for="modalQtd">Quantidade:</label>
                        <input type="number" id="modalQtd" min="1" value="1">
                    </div>
                    <div class="modal-sim-result">
                        <span>Total estimado:</span>
                        <strong id="modalTotal">R$ 0,00</strong>
                        <small id="modalEconomia" class="economia-tag" style="display:none"></small>
                    </div>
                </div>

                <div class="modal-cta">
                    <a href="#simulador" class="btn btn-primary" id="modalBtnSimulador">Quero esse modelo</a>
                    <a href="https://wa.me/55" target="_blank" rel="noopener" class="btn btn-whatsapp" id="modalBtnWhatsapp">Pedir pelo WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
</div>

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

<script>
document.addEventListener('DOMContentLoaded', function () {

    const FAIXAS = [
        { label: 'Unitário',     min: 1,  max: 2,        desc: 0    },
        { label: 'Pequeno lote', min: 3,  max: 5,        desc: 0.05 },
        { label: 'Médio lote',   min: 6,  max: 10,       desc: 0.10 },
        { label: 'Grande lote',  min: 11, max: 20,       desc: 0.15 },
        { label: 'Atacado',      min: 21, max: Infinity,  desc: 0.20 },
    ];

    function getDesconto(qtd) {
        return FAIXAS.find(f => qtd >= f.min && qtd <= f.max) || FAIXAS[0];
    }

    function formatBRL(v) {
        return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    let modeloAtual = null;

    // ── Botões "Detalhes" nos cards ──
    document.querySelectorAll('[data-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            try {
                var modelo = JSON.parse(this.getAttribute('data-modal'));
                abrirModal(modelo);
            } catch (e) {
                console.error('Erro ao parsear modelo:', e);
            }
        });
    });

    function abrirModal(modelo) {
        modeloAtual = modelo;

        document.getElementById('modalNome').textContent      = modelo.nome;
        document.getElementById('modalCategoria').textContent = modelo.categoria;
        document.getElementById('modalDesc').textContent      = modelo.descricao || 'Peça personalizada sob encomenda.';

        var imgEl = document.getElementById('modalImg');
        imgEl.style.backgroundImage = modelo.imagem
            ? 'url(/assets/img/modelos/' + modelo.imagem + ')'
            : '';
        imgEl.classList.toggle('no-img', !modelo.imagem);

        // Tabela de descontos
        var tbody = document.getElementById('discountTableBody');
        tbody.innerHTML = '';
        FAIXAS.forEach(function (f) {
            var preco = modelo.preco_base * (1 - f.desc);
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + f.label + '</td>' +
                '<td>' + f.min + (f.max === Infinity ? '+' : '–' + f.max) + ' un</td>' +
                '<td class="' + (f.desc > 0 ? 'desc-badge' : '') + '">' + (f.desc > 0 ? '-' + (f.desc * 100) + '%' : '—') + '</td>' +
                '<td><strong>' + formatBRL(preco) + '</strong></td>';
            tbody.appendChild(tr);
        });

        document.getElementById('modalQtd').value = 1;
        calcularModal();

        var overlay = document.getElementById('modeloModal');
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        setTimeout(function () { overlay.classList.add('is-open'); }, 10);
    }

    function fecharModal() {
        var overlay = document.getElementById('modeloModal');
        overlay.classList.remove('is-open');
        setTimeout(function () {
            overlay.hidden = true;
            document.body.style.overflow = '';
        }, 260);
    }

    function calcularModal() {
        if (!modeloAtual) return;
        var qtd    = parseInt(document.getElementById('modalQtd').value) || 1;
        var faixa  = getDesconto(qtd);
        var preco  = modeloAtual.preco_base * (1 - faixa.desc);
        var total  = preco * qtd;
        var economia = (modeloAtual.preco_base - preco) * qtd;

        document.getElementById('modalTotal').textContent = formatBRL(total);
        var econEl = document.getElementById('modalEconomia');
        if (faixa.desc > 0) {
            econEl.textContent = 'Você economiza ' + formatBRL(economia);
            econEl.style.display = 'inline-block';
        } else {
            econEl.style.display = 'none';
        }

        document.querySelectorAll('#discountTableBody tr').forEach(function (tr, i) {
            tr.classList.toggle('row-active', FAIXAS[i] && FAIXAS[i].label === faixa.label);
        });
    }

    // ── Eventos do modal ──
    document.getElementById('modalQtd').addEventListener('input', calcularModal);

    document.getElementById('modalCloseBtn').addEventListener('click', fecharModal);

    document.getElementById('modalBtnSimulador').addEventListener('click', fecharModal);

    document.getElementById('modeloModal').addEventListener('click', function (e) {
        if (e.target === this) fecharModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') fecharModal();
    });

    // ── Botões "Quero esse modelo" nos cards → seleciona no simulador ──
    document.querySelectorAll('[data-model-id]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id  = this.dataset.modelId;
            var sel = document.getElementById('modelo_id');
            if (sel) {
                sel.value = id;
                sel.dispatchEvent(new Event('change'));
            }
        });
    });

});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
