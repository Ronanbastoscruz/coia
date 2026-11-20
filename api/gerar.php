<?php
/**
 * api/gerar.php — gera um artigo com IA e grava como rascunho.
 * Entrada (JSON): tema, palavras_chave, tamanho, ia, observacoes, [artigo_id para "Gerar de novo"]
 */
require_once __DIR__ . '/_api.php';
require_once __DIR__ . '/lib/ia.php';
require_once __DIR__ . '/lib/html_limpo.php';
require_once __DIR__ . '/lib/consumo.php';

$in = api_entrada();

$tema = coia_texto($in['tema'] ?? '', 300);
if ($tema === '') api_erro('Preencha o tema do artigo.');

$palavras = array_slice(array_values(array_filter(array_map(
    fn($p) => coia_texto($p, 60),
    explode(',', (string)($in['palavras_chave'] ?? ''))
))), 0, 5);

$tamanho = (string)($in['tamanho'] ?? 'medio');
if (!isset(COIA_TAMANHOS[$tamanho])) $tamanho = 'medio';

$ia = (string)($in['ia'] ?? '');
if (!in_array($ia, coia_ias_disponiveis(), true)) api_erro('Esta IA ainda não está configurada.');

$observacoes = coia_texto($in['observacoes'] ?? '', 500);

// "Gerar de novo": o artigo tem que ser desta empresa
$artigoId = (string)($in['artigo_id'] ?? '');
$existente = null;
if ($artigoId !== '') {
    $existente = artigo_do_dono($artigoId);
    if (!$existente) api_erro('Artigo não encontrado.', 404);
}

$cadastro = coia_cadastro();
if (!$cadastro) api_erro('Complete seu cadastro antes de gerar o primeiro artigo.', 400, ['ir' => 'cadastro.php']);

$bloqueio = coia_pode_gerar();
if ($bloqueio !== '') api_erro($bloqueio, 429);

// Marca a trava e LIBERA a sessão (a IA pode demorar; assim as outras telas não ficam travadas)
$_SESSION['coia_ultima_geracao'] = time();
session_write_close();

try {
    $art = coia_gerar_artigo($ia, [
        'tema' => $tema, 'palavras_chave' => $palavras, 'tamanho' => $tamanho, 'observacoes' => $observacoes,
    ], $cadastro);
} catch (Exception $e) {
    try { coia_registrar_consumo($ia, '', 0, 0, 'erro'); } catch (Exception $e2) {}
    api_erro($e->getMessage(), 502);
}

$corpo = coia_html_limpo($art['corpo_html']);
coia_registrar_consumo($ia, $art['modelo'], $art['tokens_entrada'], $art['tokens_saida'], 'ok');

$agora = date('c');
$id = $existente ? $existente['id'] : coia_novo_id();
fa_set('coia_artigos', $id, [
    'empresa_cnpj'   => $_SESSION['empresa_cnpj'],
    'autor'          => (string)($cadastro['nome'] ?? ''),
    'criado_por'     => $_SESSION['usuario_email'],
    'tema'           => $tema,
    'palavras_chave' => $palavras,
    'tamanho'        => $tamanho,
    'observacoes'    => $observacoes,
    'ia'             => $ia,
    'modelo'         => $art['modelo'],
    'titulo'         => $art['titulo'],
    'resumo'         => $art['resumo'],
    'corpo_html'     => $corpo,
    'palavras'       => coia_contar_palavras($corpo),
    'status'         => 'rascunho',
    'criado_em'      => $existente['criado_em'] ?? $agora,
    'atualizado_em'  => $agora,
]);

api_resposta(['ok' => true, 'id' => $id]);
