<?php
require_once __DIR__ . '/_sessao.php';
require_once __DIR__ . '/api/lib/ia.php';

$cad = coia_cadastro() ?: [];
$primeiro = empty($cad);
$titulo = 'Cadastro';
$pagina = 'cadastro';
require __DIR__ . '/_topo.php';
?>
<h3 class="mb-1">Cadastro da empresa</h3>
<p class="text-muted">Esses dados ajudam a IA a escrever com a cara da sua empresa.</p>

<?php if ($primeiro): ?>
    <div class="alert alert-info" style="max-width:720px">Bem-vindo ao CoIA! Complete seu cadastro para gerar o primeiro artigo.</div>
<?php endif; ?>

<form class="card p-4" style="max-width:720px" id="formCadastro" novalidate>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="c-nome">Nome da empresa *</label>
            <input class="form-control" id="c-nome" name="nome" maxlength="120" required value="<?= h($cad['nome'] ?? ($_SESSION['empresa_nome'] ?? '')) ?>">
            <div class="form-text">Aparece como autor dos artigos.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="c-site">Site</label>
            <input class="form-control" id="c-site" name="site" maxlength="200" placeholder="www.suaempresa.com.br" value="<?= h($cad['site'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="c-ramo">Ramo / segmento *</label>
            <input class="form-control" id="c-ramo" name="ramo" maxlength="120" required placeholder="Ex.: Padaria e confeitaria" value="<?= h($cad['ramo'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="c-cidade">Cidade</label>
            <input class="form-control" id="c-cidade" name="cidade" maxlength="120" placeholder="Ex.: Belo Horizonte - MG" value="<?= h($cad['cidade'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label" for="c-publico">Público-alvo</label>
            <input class="form-control" id="c-publico" name="publico" maxlength="200" placeholder="Ex.: famílias do bairro" value="<?= h($cad['publico'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="c-tom">Tom de voz</label>
            <select class="form-select" id="c-tom" name="tom">
                <?php foreach (COIA_TONS as $t): ?>
                    <option<?= ($cad['tom'] ?? 'Amigável') === $t ? ' selected' : '' ?>><?= h($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Salvar cadastro</button>
    </div>
</form>
<?php require __DIR__ . '/_rodape.php'; ?>
