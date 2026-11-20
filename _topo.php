<?php
if (!function_exists('coia_logado')) exit; // não abre sozinho
/**
 * _topo.php — começo do layout das telas (menu lateral).
 * Antes de incluir, defina: $titulo (aba do navegador) e $pagina (gerar|artigos|cadastro).
 */
$__nomeEmpresa = '';
try { $__nomeEmpresa = coia_cadastro()['nome'] ?? ''; } catch (Exception $e) {}
if ($__nomeEmpresa === '') $__nomeEmpresa = ($_SESSION['empresa_nome'] ?? '') ?: 'Sua empresa';
$__menu = [
    'gerar'    => ['gerar.php', 'bi-stars', 'Gerar artigo'],
    'artigos'  => ['historico.php', 'bi-journal-text', 'Meus artigos'],
    'cadastro' => ['cadastro.php', 'bi-building', 'Cadastro'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <meta name="csrf" content="<?= h(coia_csrf()) ?>">
    <title><?= h($titulo ?? 'CoIA') ?> — CoIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="assets/coia.css?v=20261120a" rel="stylesheet">
</head>
<body>
<nav class="lateral p-3" id="lateral">
    <div class="marca mb-4 px-2">Co<span>IA</span></div>
    <div class="usuario p-3 mb-4">
        <div class="fw-semibold text-white text-break"><?= h($__nomeEmpresa) ?></div>
        <div class="small">Editor chefe</div>
    </div>
    <?php foreach ($__menu as $chave => [$link, $icone, $texto]): ?>
        <a class="item<?= ($pagina ?? '') === $chave ? ' ativo' : '' ?>" href="<?= $link ?>"><i class="bi <?= $icone ?>"></i> <?= $texto ?></a>
    <?php endforeach; ?>
    <hr class="border-secondary">
    <a class="item" href="index.php?action=sair"><i class="bi bi-box-arrow-left"></i> Sair</a>
</nav>
<main class="conteudo">
    <button type="button" class="btn btn-outline-secondary d-lg-none mb-3" id="btnMenu"><i class="bi bi-list"></i> Menu</button>
