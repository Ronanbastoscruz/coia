<?php
require_once __DIR__ . '/_sessao.php';
require_once __DIR__ . '/api/lib/ia.php';
require_once __DIR__ . '/api/lib/consumo.php';

if (!coia_cadastro()) {
    header('Location: cadastro.php');
    exit;
}

$ias = coia_ias_disponiveis();
$nomesIa = ['openai' => 'OpenAI', 'groq' => 'Groq'];
$limite = coia_limite_dia();
$usadas = coia_usadas_hoje();
$esgotado = $usadas >= $limite;

$titulo = 'Gerar artigo';
$pagina = 'gerar';
require __DIR__ . '/_topo.php';
?>
<h3 class="mb-1">Gerar artigo</h3>
<p class="text-muted">Diga o tema e a IA escreve um rascunho para você revisar.</p>

<form class="card p-4" style="max-width:720px" id="formGerar" novalidate>
    <div class="mb-3">
        <label class="form-label" for="g-tema">Tema do artigo *</label>
        <input class="form-control" id="g-tema" name="tema" maxlength="300" required placeholder="Ex.: 5 dicas para conservar o pão francês por mais tempo">
    </div>
    <div class="mb-3">
        <label class="form-label" for="g-palavras">Palavras-chave <span class="text-muted small">(até 5, separadas por vírgula)</span></label>
        <input class="form-control" id="g-palavras" name="palavras_chave" maxlength="300" placeholder="pão francês, conservar pão, padaria">
    </div>
    <div class="mb-3">
        <span class="form-label d-block">Tamanho</span>
        <div class="btn-group flex-wrap" role="group">
            <input type="radio" class="btn-check" name="tamanho" id="tam-curto" value="curto"><label class="btn btn-outline-secondary" for="tam-curto">Curto (~500)</label>
            <input type="radio" class="btn-check" name="tamanho" id="tam-medio" value="medio" checked><label class="btn btn-outline-secondary" for="tam-medio">Médio (~900)</label>
            <input type="radio" class="btn-check" name="tamanho" id="tam-longo" value="longo"><label class="btn btn-outline-secondary" for="tam-longo">Longo (~1500)</label>
        </div>
    </div>
    <div class="mb-3">
        <span class="form-label d-block">IA</span>
        <?php if (!$ias): ?>
            <div class="alert alert-warning mb-0">Nenhuma IA configurada ainda. Fale com o suporte M3R.</div>
        <?php else: ?>
            <div class="btn-group" role="group">
                <?php foreach ($ias as $i => $ia): ?>
                    <input type="radio" class="btn-check" name="ia" id="ia-<?= h($ia) ?>" value="<?= h($ia) ?>"<?= $i === 0 ? ' checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="ia-<?= h($ia) ?>"><?= h($nomesIa[$ia] ?? $ia) ?></label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="mb-4">
        <label class="form-label" for="g-obs">Observações <span class="text-muted small">(opcional)</span></label>
        <textarea class="form-control" id="g-obs" name="observacoes" rows="2" maxlength="500" placeholder="Ex.: terminar convidando para conhecer a loja"></textarea>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-3">
        <button class="btn btn-primary btn-lg" type="submit" id="btnGerar"<?= ($esgotado || !$ias) ? ' disabled' : '' ?>>
            <i class="bi bi-stars"></i> Gerar artigo com IA
        </button>
        <span class="text-muted small">Hoje você usou <b><?= $usadas ?> de <?= $limite ?></b> gerações.</span>
    </div>
    <?php if ($esgotado): ?>
        <div class="alert alert-warning mt-3 mb-0">Você atingiu o limite de <?= $limite ?> artigos por dia. Novas gerações liberam amanhã às 00h.</div>
    <?php endif; ?>
    <div id="gerando" class="alert alert-info mt-3 mb-0 d-none">
        <span class="spinner-border spinner-border-sm me-2"></span>Escrevendo seu artigo... isso leva até 1 minuto. Não feche esta página.
    </div>
</form>
<?php require __DIR__ . '/_rodape.php'; ?>
