<?php
require_once __DIR__ . '/_sessao.php';

$artigos = fa_query('coia_artigos', ['empresa_cnpj' => $_SESSION['empresa_cnpj']]);
usort($artigos, fn($a, $b) => strcmp($b['criado_em'] ?? '', $a['criado_em'] ?? ''));

$busca = coia_texto($_GET['q'] ?? '', 100);
if ($busca !== '') {
    $artigos = array_values(array_filter($artigos, fn($a) => mb_stripos($a['titulo'] ?? '', $busca) !== false));
}
$nomesIa = ['openai' => 'OpenAI', 'groq' => 'Groq'];

$titulo = 'Meus artigos';
$pagina = 'artigos';
require __DIR__ . '/_topo.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h3 class="mb-0">Meus artigos</h3>
    <form method="get" class="d-flex gap-2" style="max-width:320px">
        <input class="form-control" name="q" placeholder="Buscar por título..." value="<?= h($busca) ?>">
        <button class="btn btn-outline-secondary" title="Buscar"><i class="bi bi-search"></i></button>
    </form>
</div>

<?php if (!$artigos): ?>
    <div class="card p-5 text-center text-muted">
        <?php if ($busca !== ''): ?>
            Nenhum artigo com "<?= h($busca) ?>" no título.
        <?php else: ?>
            Você ainda não gerou nenhum artigo.
            <div class="mt-3"><a class="btn btn-primary" href="gerar.php"><i class="bi bi-stars"></i> Gerar o primeiro</a></div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Data</th><th>Título</th><th>IA</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                <?php foreach ($artigos as $a): $pronto = ($a['status'] ?? '') === 'pronto'; ?>
                    <tr>
                        <td class="text-nowrap"><?= h(!empty($a['criado_em']) ? date('d/m/Y', strtotime($a['criado_em'])) : '') ?></td>
                        <td><?= h($a['titulo'] ?? '') ?></td>
                        <td><?= h($nomesIa[$a['ia'] ?? ''] ?? '') ?></td>
                        <td><span class="badge <?= $pronto ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $pronto ? 'Pronto' : 'Rascunho' ?></span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="revisar.php?id=<?= h($a['id']) ?>">Abrir</a>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-excluir="<?= h($a['id']) ?>" title="Excluir"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/_rodape.php'; ?>
