<?php
/**
 * api/cadastro.php — salva o cadastro da empresa logada (1 documento por CNPJ).
 * Entrada (JSON): nome, site, ramo, cidade, publico, tom
 */
require_once __DIR__ . '/_api.php';
require_once __DIR__ . '/lib/ia.php';

$in = api_entrada();

$nome = coia_texto($in['nome'] ?? '', 120);
$ramo = coia_texto($in['ramo'] ?? '', 120);
if ($nome === '' || $ramo === '') api_erro('Preencha o nome da empresa e o ramo.');

$site = coia_texto($in['site'] ?? '', 200);
if ($site !== '' && !preg_match('#^(https?://)?[a-z0-9.-]+\.[a-z]{2,}(/\S*)?$#i', $site)) {
    api_erro('O site parece inválido. Exemplo: www.suaempresa.com.br');
}

$tom = (string)($in['tom'] ?? 'Amigável');
if (!in_array($tom, COIA_TONS, true)) $tom = 'Amigável';

$antes = coia_cadastro();
fa_set('coia_clientes', coia_id_empresa(), [
    'empresa_cnpj'  => $_SESSION['empresa_cnpj'],
    'nome'          => $nome,
    'site'          => $site,
    'ramo'          => $ramo,
    'cidade'        => coia_texto($in['cidade'] ?? '', 120),
    'publico'       => coia_texto($in['publico'] ?? '', 200),
    'tom'           => $tom,
    'criado_em'     => $antes['criado_em'] ?? date('c'),
    'atualizado_em' => date('c'),
]);

api_resposta(['ok' => true]);
