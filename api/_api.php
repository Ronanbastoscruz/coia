<?php
/**
 * api/_api.php — proteção comum de TODAS as ações (api/*.php).
 * Exige: login de Editor chefe, método POST, token CSRF e mesma origem.
 * Responde sempre em JSON.
 */
require_once __DIR__ . '/../_inicio.php';
require_once __DIR__ . '/lib/firebase_admin.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function api_resposta($dados, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_erro($mensagem, $codigo = 400, $extra = []) {
    api_resposta(['ok' => false, 'erro' => $mensagem] + $extra, $codigo);
}

// Qualquer erro inesperado vira uma resposta JSON simples (sem detalhes internos)
set_exception_handler(function ($e) {
    error_log('CoIA API: ' . $e->getMessage());
    api_erro('Não foi possível concluir agora. Tente de novo em instantes.', 500);
});

if (!coia_logado()) {
    api_erro('Sua sessão expirou. Entre de novo pelo painel M3R.', 401, ['sessao' => false]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_erro('Método não permitido.', 405);
}
$__origem = $_SERVER['HTTP_ORIGIN'] ?? '';
$__host = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
if ($__origem !== '' && parse_url($__origem, PHP_URL_HOST) !== $__host) {
    api_erro('Origem não permitida.', 403);
}
if (!coia_csrf_ok()) {
    api_erro('Página desatualizada. Recarregue e tente de novo.', 403);
}

/** Corpo JSON enviado pelo navegador. */
function api_entrada() {
    $dados = json_decode((string)file_get_contents('php://input'), true);
    return is_array($dados) ? $dados : [];
}
