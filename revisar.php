<?php
require_once __DIR__ . '/_sessao.php';
require_once __DIR__ . '/api/lib/consumo.php';

$art = artigo_do_dono((string)($_GET['id'] ?? ''));
if (!$art) {
    header('Location: historico.php');
    exit;
}
$nomesIa = ['openai' => 'OpenAI', 'grok' => 'Grok'];
$pronto = ($art['status'] ?? '') === 'pronto';
$quando = !empty($art['atualizado_em']) ? date('d/m/Y H:i', strtotime($art['atualizado_em'])) : '';

$titulo = 'Revisar artigo';
$pagina = 'artigos';
require __DIR__ . '/_topo.php';
?>
<!-- Dados do artigo para o "Gerar de novo" (lidos pelo coia.js) -->
<div id="artigo" hidden
     data-id="<?= h($art['id']) ?>"
     data-tema="<?= h($art['tema'] ?? '') ?>"
     data-palavras="<?= h(implode(', ', (array)($art['palavras_chave'] ?? []))) ?>"
     data-tamanho="<?= h($art['tamanho'] ?? 'medio') ?>"
     data-ia="<?= h($art['ia'] ?? '') ?>"
     data-obs="<?= h($art['observacoes'] ?? '') ?>"></div>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h3 class="mb-0">Revisar artigo</h3>
        <span class="badge <?= $pronto ? 'text-bg-success' : 'text-bg-secondary' ?>" id="badgeStatus"><?= $pronto ? 'Pronto' : 'Rascunho' ?></span>
        <span class="small text-muted">gerado com <?= h($nomesIa[$art['ia'] ?? ''] ?? '') ?> · <?= h($quando) ?></span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary" id="btnRegerar"><i class="bi bi-arrow-repeat"></i> Gerar de novo</button>
        <button type="button" class="btn btn-outline-primary" id="btnSalvar"><i class="bi bi-check2"></i> Salvar</button>
        <button type="button" class="btn btn-primary" id="btnPronto"><i class="bi bi-check2-all"></i> Salvar como pronto</button>
    </div>
</div>
<div id="gerando" class="alert alert-info d-none">
    <span class="spinner-border spinner-border-sm me-2"></span>Escrevendo seu artigo... isso leva até 1 minuto. Não feche esta página.
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card p-4">
            <label class="form-label" for="r-titulo">Título <span class="text-muted small float-end" data-contador="r-titulo" data-max="60"></span></label>
            <input class="form-control form-control-lg mb-3" id="r-titulo" maxlength="120" value="<?= h($art['titulo'] ?? '') ?>">

            <label class="form-label" for="r-resumo">Resumo <span class="text-muted small float-end" data-contador="r-resumo" data-max="160"></span></label>
            <textarea class="form-control mb-3" id="r-resumo" rows="2" maxlength="300"><?= h($art['resumo'] ?? '') ?></textarea>

            <span class="form-label d-block">Texto</span>
            <div class="editor-barra btn-toolbar border rounded-top p-2 bg-light gap-1" role="toolbar">
                <button type="button" class="btn btn-sm btn-light" data-cmd="bold" title="Negrito"><i class="bi bi-type-bold"></i></button>
                <button type="button" class="btn btn-sm btn-light" data-cmd="italic" title="Itálico"><i class="bi bi-type-italic"></i></button>
                <button type="button" class="btn btn-sm btn-light" data-cmd="h2" title="Subtítulo"><i class="bi bi-type-h2"></i></button>
                <button type="button" class="btn btn-sm btn-light" data-cmd="p" title="Parágrafo"><i class="bi bi-paragraph"></i></button>
                <button type="button" class="btn btn-sm btn-light" data-cmd="insertUnorderedList" title="Lista"><i class="bi bi-list-ul"></i></button>
                <button type="button" class="btn btn-sm btn-light" data-cmd="link" title="Link"><i class="bi bi-link-45deg"></i></button>
            </div>
            <!-- O corpo já foi limpo pelo servidor (só tags seguras) -->
            <div class="editor-area" id="r-corpo" contenteditable="true"><?= $art['corpo_html'] ?? '' ?></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 mb-3">
            <h6>Usar o artigo</h6>
            <p class="small text-muted">Copie e cole no seu blog ou baixe o arquivo.</p>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-primary" id="btnCopiarHtml"><i class="bi bi-code-slash"></i> Copiar HTML</button>
                <button type="button" class="btn btn-outline-primary" id="btnCopiarTexto"><i class="bi bi-clipboard"></i> Copiar texto</button>
                <button type="button" class="btn btn-outline-secondary" id="btnBaixar"><i class="bi bi-download"></i> Baixar (.html)</button>
            </div>
        </div>
        <div class="card p-4">
            <h6>Detalhes</h6>
            <div class="small text-muted mb-2">Autor: <b><?= h($art['autor'] ?? '') ?></b></div>
            <?php if (!empty($art['palavras_chave'])): ?>
                <div class="small text-muted mb-1">Palavras-chave:</div>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ((array)$art['palavras_chave'] as $p): ?><span class="tag"><?= h($p) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="small text-muted mt-2">~<span id="contaPalavras"><?= intval($art['palavras'] ?? 0) ?></span> palavras</div>
            <div class="small text-muted mt-2">Gerações hoje: <?= coia_usadas_hoje() ?> de <?= coia_limite_dia() ?></div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_rodape.php'; ?>
