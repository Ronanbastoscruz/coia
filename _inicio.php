<?php
/**
 * _inicio.php — incluído no topo de TODO arquivo PHP do CoIA.
 * - Sessão própria (COIA_SESS) para não misturar com o Agendador (mesmo domínio).
 * - Cookie seguro e cabeçalhos básicos de proteção.
 */
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('COIA_SESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
// Só carrega scripts do próprio CoIA e do Bootstrap (nada de script embutido na página)
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; "
    . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; "
    . "img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");

require_once __DIR__ . '/api/lib/seguranca.php';
