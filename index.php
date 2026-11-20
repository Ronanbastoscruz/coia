<?php
/**
 * index.php — entrada do CoIA.
 * Login SOMENTE pela API M3R: /api/sso/connect.php → ticket → coia/index.php?ticket=
 * Na versão 1 só entra o Editor chefe (nível 1).
 */
require_once __DIR__ . '/_inicio.php';

$ticket = $_GET['ticket'] ?? '';
$action = $_GET['action'] ?? '';
$erro = '';

if ($action === 'sair') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?saiu=1');
    exit;
}

if (is_string($ticket) && $ticket !== '') {
    $ch = curl_init('https://m3r.com.br/api/sso/validate.php?ticket=' . urlencode($ticket));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $resp = json_decode((string)curl_exec($ch), true);
    curl_close($ch);

    $ok = is_array($resp) && !empty($resp['success']);

    // 🔒 Só aceita projetos/chaves autorizados em privado/coia_sso_permitidos.json:
    //    { "projetos": ["id do projeto CoIA"], "tenants": ["..."] }  (lista vazia = não filtra)
    //    SEM o arquivo, NINGUÉM entra (diferente do Agendador).
    if ($ok) {
        $perm = coia_ler_privado('coia_sso_permitidos.json');
        if ($perm === null) {
            error_log('CoIA SSO: coia_sso_permitidos.json não encontrado — acesso recusado.');
            $ok = false;
        } else {
            $okProj = empty($perm['projetos']) || in_array((string)($resp['projeto_id'] ?? ''), (array)$perm['projetos'], true);
            $okTen  = empty($perm['tenants'])  || in_array((string)($resp['tenant_id'] ?? ''), (array)$perm['tenants'], true);
            if (!$okProj || !$okTen) $ok = false;
        }
    }

    if ($ok) {
        $usr = $resp['usuario'] ?? [];
        $emp = $resp['empresa'] ?? [];
        $cd  = $resp['dados'] ?? [];
        $email = trim((string)($usr['email'] ?? ''));
        $nivel = intval($usr['nivel_acesso'] ?? 0);
        $cnpj  = trim((string)($emp['cnpj'] ?? ($cd['cnpj'] ?? '')));

        if ($email === '' || $cnpj === '') {
            $erro = 'Seu acesso veio incompleto (e-mail ou CNPJ). Fale com o suporte M3R.';
        } elseif ($nivel !== 1) {
            $erro = 'Seu usuário não tem acesso ao CoIA nesta versão.';
        } else {
            // 🔒 Sessão nova e limpa
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['usuario_nome']  = trim((string)($usr['nome'] ?? ''));
            $_SESSION['usuario_email'] = $email;
            $_SESSION['nivel_acesso']  = 1;
            $_SESSION['empresa_cnpj']  = $cnpj;
            $_SESSION['empresa_nome']  = trim((string)($emp['nome'] ?? ($cd['nome'] ?? '')));
            header('Location: gerar.php');
            exit;
        }
    } else {
        $erro = 'Falha na validação do acesso. Volte ao painel M3R e tente de novo.';
    }
}

if (!$erro && coia_logado()) {
    header('Location: gerar.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>CoIA — Entrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="assets/coia.css?v=20261120a" rel="stylesheet">
</head>
<body>
<div class="entrada">
    <div class="card p-5 text-center" style="max-width:440px">
        <div class="fs-2 fw-bold mb-3">Co<span class="cor-destaque">IA</span></div>
        <div class="fs-1 mb-2 text-secondary"><i class="bi bi-shield-lock"></i></div>
        <?php if ($erro): ?>
            <div class="alert alert-warning"><?= h($erro) ?></div>
        <?php elseif (isset($_GET['saiu'])): ?>
            <p class="text-muted">Você saiu do CoIA.</p>
        <?php endif; ?>
        <p class="text-muted mb-0">Acesso só pelo painel M3R.</p>
    </div>
</div>
</body>
</html>
