<?php
/**
 * api/lib/consumo.php — limite de uso da IA.
 * - Até N gerações por dia por empresa (limite_dia_empresa em coia-ia.json; padrão 5).
 * - Uma geração a cada 30 segundos por sessão.
 * - Toda geração fica registrada na coleção coia_consumo.
 */

const COIA_TRAVA_SEGUNDOS = 30;

function coia_limite_dia() {
    $cfg = coia_ler_privado('coia-ia.json') ?: [];
    return max(1, intval($cfg['limite_dia_empresa'] ?? 5));
}

/** Quantas gerações a empresa logada já fez hoje (só as que deram certo contam). */
function coia_usadas_hoje() {
    $hoje = date('Y-m-d');
    $regs = fa_query('coia_consumo', ['empresa_cnpj' => $_SESSION['empresa_cnpj'], 'dia' => $hoje]);
    return count(array_filter($regs, fn($r) => ($r['status'] ?? '') === 'ok'));
}

/** Devolve uma mensagem de bloqueio, ou '' se pode gerar. */
function coia_pode_gerar() {
    $ultima = intval($_SESSION['coia_ultima_geracao'] ?? 0);
    $falta = COIA_TRAVA_SEGUNDOS - (time() - $ultima);
    if ($falta > 0) return "Aguarde $falta segundos para gerar de novo.";

    $limite = coia_limite_dia();
    if (coia_usadas_hoje() >= $limite) {
        return "Você atingiu o limite de $limite artigos por dia. Novas gerações liberam amanhã às 00h.";
    }
    return '';
}

function coia_registrar_consumo($ia, $modelo, $tokensEntrada, $tokensSaida, $status) {
    fa_set('coia_consumo', coia_novo_id(), [
        'empresa_cnpj'   => $_SESSION['empresa_cnpj'],
        'email'          => $_SESSION['usuario_email'],
        'dia'            => date('Y-m-d'),
        'criado_em'      => date('c'),
        'ia'             => $ia,
        'modelo'         => $modelo,
        'tokens_entrada' => intval($tokensEntrada),
        'tokens_saida'   => intval($tokensSaida),
        'status'         => $status,
    ]);
}
