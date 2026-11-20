<?php
/**
 * api/salvar.php — salva as edições de um artigo da empresa logada.
 * Entrada (JSON): id, titulo, resumo, corpo_html, status ("rascunho" | "pronto")
 */
require_once __DIR__ . '/_api.php';
require_once __DIR__ . '/lib/html_limpo.php';

$in = api_entrada();
$art = artigo_do_dono((string)($in['id'] ?? ''));
if (!$art) api_erro('Artigo não encontrado.', 404);

$titulo = coia_texto($in['titulo'] ?? '', 120);
if ($titulo === '') api_erro('O título não pode ficar vazio.');

$corpo = coia_html_limpo(mb_substr((string)($in['corpo_html'] ?? ''), 0, 100000));
if ($corpo === '') api_erro('O texto do artigo não pode ficar vazio.');

$status = ($in['status'] ?? '') === 'pronto' ? 'pronto' : 'rascunho';

fa_update('coia_artigos', $art['id'], [
    'titulo'        => $titulo,
    'resumo'        => coia_texto($in['resumo'] ?? '', 300),
    'corpo_html'    => $corpo,
    'palavras'      => coia_contar_palavras($corpo),
    'status'        => $status,
    'atualizado_em' => date('c'),
]);

api_resposta(['ok' => true, 'corpo_html' => $corpo, 'status' => $status]);
