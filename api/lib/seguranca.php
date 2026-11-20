<?php
/**
 * api/lib/seguranca.php — funções de proteção usadas em todo o CoIA.
 */

/** Pasta privada do servidor (fora do site). É COMPARTILHADA: arquivos do CoIA começam com "coia". */
function coia_pasta_privada() {
    $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($root) return dirname(rtrim($root, '/\\')) . '/privado';
    return '/home/u838921894/domains/m3r.com.br/privado';
}

/** Lê um JSON da pasta privada (só nomes começando com "coia"). Devolve null se não existir. */
function coia_ler_privado($nome) {
    if (strpos($nome, 'coia') !== 0 || basename($nome) !== $nome) {
        throw new Exception('Nome de arquivo privado inválido.');
    }
    $arq = coia_pasta_privada() . '/' . $nome;
    if (!is_readable($arq)) return null;
    $dados = json_decode((string)file_get_contents($arq), true);
    return is_array($dados) ? $dados : null;
}

/** true se o usuário está logado como Editor chefe (nível 1) de uma empresa. */
function coia_logado() {
    return !empty($_SESSION['usuario_email'])
        && !empty($_SESSION['empresa_cnpj'])
        && intval($_SESSION['nivel_acesso'] ?? 0) === 1;
}

/** Token contra CSRF (um por sessão). */
function coia_csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function coia_csrf_ok() {
    $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['csrf']) && is_string($enviado) && hash_equals($_SESSION['csrf'], $enviado);
}

/** Escapa texto para HTML. */
function h($txt) {
    return htmlspecialchars((string)$txt, ENT_QUOTES, 'UTF-8');
}

/** Gera um id aleatório para documentos. */
function coia_novo_id() {
    return bin2hex(random_bytes(10));
}

/**
 * Busca um artigo SÓ se for da empresa logada. Senão devolve null.
 * Toda leitura/edição/exclusão por id passa por aqui.
 */
function artigo_do_dono($id) {
    if (!is_string($id) || !preg_match('/^[a-f0-9]{20}$/', $id)) return null;
    $art = fa_get('coia_artigos', $id);
    if (!$art || ($art['empresa_cnpj'] ?? '') !== $_SESSION['empresa_cnpj']) return null;
    return $art;
}

/** Id do documento da empresa no Firestore (CNPJ só com números). */
function coia_id_empresa() {
    return preg_replace('/\D/', '', $_SESSION['empresa_cnpj'] ?? '') ?: 'sem-cnpj';
}

/** Cadastro da empresa logada (ou null se ainda não preencheu). */
function coia_cadastro() {
    static $cad = false;
    if ($cad === false) $cad = fa_get('coia_clientes', coia_id_empresa());
    return $cad;
}

/** Texto simples com limite de tamanho (para campos de formulário). */
function coia_texto($v, $max) {
    $v = trim(preg_replace('/\s+/u', ' ', (string)$v));
    return mb_substr($v, 0, $max);
}
