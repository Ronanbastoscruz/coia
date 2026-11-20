<?php
/**
 * entrar-teste.php — ENTRADA DE TESTE, enquanto o CoIA não está ligado ao SSO da API M3R.
 * Só funciona se existir privado/coia_teste.json:
 *   { "chave": "texto longo e secreto", "email": "...", "nome": "...", "cnpj": "...", "empresa": "..." }
 * Para DESLIGAR: apague privado/coia_teste.json do servidor (e este arquivo, quando o SSO estiver pronto).
 */
require_once __DIR__ . '/_inicio.php';

$cfg = coia_ler_privado('coia_teste.json');
if (!$cfg || empty($cfg['chave']) || strlen($cfg['chave']) < 24) {
    http_response_code(404);
    exit('Não encontrado.');
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pequena espera para dificultar tentativas em massa
    usleep(800000);
    $digitada = (string)($_POST['chave'] ?? '');
    if (hash_equals((string)$cfg['chave'], $digitada)) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['usuario_nome']  = (string)($cfg['nome'] ?? 'Teste');
        $_SESSION['usuario_email'] = (string)($cfg['email'] ?? 'teste@m3r.com.br');
        $_SESSION['nivel_acesso']  = 1;
        $_SESSION['empresa_cnpj']  = (string)($cfg['cnpj'] ?? '00.000.000/0001-00');
        $_SESSION['empresa_nome']  = (string)($cfg['empresa'] ?? 'Empresa de teste');
        header('Location: gerar.php');
        exit;
    }
    $erro = 'Chave errada.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>CoIA — Entrada de teste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/coia.css?v=20261120b" rel="stylesheet">
</head>
<body>
<div class="entrada">
    <form method="post" class="card p-5" style="max-width:420px;width:100%">
        <div class="fs-3 fw-bold mb-1 text-center">Co<span class="cor-destaque">IA</span></div>
        <p class="text-muted text-center small mb-4">Entrada de teste (temporária)</p>
        <?php if ($erro): ?><div class="alert alert-danger"><?= h($erro) ?></div><?php endif; ?>
        <label class="form-label" for="chave">Chave de teste</label>
        <input type="password" class="form-control mb-3" id="chave" name="chave" autocomplete="off" required autofocus>
        <button class="btn btn-primary w-100">Entrar</button>
    </form>
</div>
</body>
</html>
