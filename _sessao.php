<?php
/**
 * _sessao.php — proteção das TELAS do CoIA (incluir no topo de cada tela).
 * Sem login de Editor chefe → volta para a entrada.
 */
require_once __DIR__ . '/_inicio.php';
require_once __DIR__ . '/api/lib/firebase_admin.php';

// Erro inesperado (ex.: banco fora do ar) → página simples, sem detalhes internos
set_exception_handler(function ($e) {
    error_log('CoIA tela: ' . $e->getMessage());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>CoIA</title></head>'
       . '<body style="font-family:sans-serif;text-align:center;padding:60px">'
       . '<h2>Não foi possível abrir esta página agora.</h2><p>Tente de novo em instantes.</p>'
       . '<p><a href="gerar.php">Voltar</a></p></body></html>';
});

if (!coia_logado()) {
    header('Location: index.php');
    exit;
}
coia_csrf();
