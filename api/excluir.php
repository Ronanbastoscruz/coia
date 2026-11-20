<?php
/**
 * api/excluir.php — exclui um artigo da empresa logada.
 * Entrada (JSON): id
 */
require_once __DIR__ . '/_api.php';

$in = api_entrada();
$art = artigo_do_dono((string)($in['id'] ?? ''));
if (!$art) api_erro('Artigo não encontrado.', 404);

fa_delete('coia_artigos', $art['id']);
api_resposta(['ok' => true]);
