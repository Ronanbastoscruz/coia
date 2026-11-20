<?php
/**
 * api/lib/firebase_admin.php
 * Acesso de ADMINISTRADOR ao Firestore do CoIA, feito só pelo servidor (sem Composer).
 * Baseado no do Agendador, mas:
 * - usa a chave privado/coia-firebase-admin.json (projeto Firebase NOVO, só do CoIA);
 * - o id do projeto é lido da própria chave;
 * - sem custom token / locais / contas de login (o navegador não acessa o Firestore).
 */
require_once __DIR__ . '/seguranca.php';

function fa_chave() {
    static $chave = null;
    if ($chave !== null) return $chave;
    $chave = coia_ler_privado('coia-firebase-admin.json');
    if (empty($chave['private_key']) || empty($chave['client_email']) || empty($chave['project_id'])) {
        throw new Exception('Chave do Firebase do CoIA não encontrada ou inválida.');
    }
    return $chave;
}

function fa_base() {
    return 'https://firestore.googleapis.com/v1/projects/' . fa_chave()['project_id'] . '/databases/(default)/documents';
}

function fa_b64($dados) {
    return rtrim(strtr(base64_encode($dados), '+/', '-_'), '=');
}

function fa_jwt(array $payload) {
    $chave = fa_chave();
    $cabecalho = fa_b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $corpo = fa_b64(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $assinatura = '';
    if (!openssl_sign("$cabecalho.$corpo", $assinatura, $chave['private_key'], 'sha256WithRSAEncryption')) {
        throw new Exception('Falha ao assinar o token.');
    }
    return "$cabecalho.$corpo." . fa_b64($assinatura);
}

/** Token de acesso do Google (guardado por ~1h num cache com nome do CoIA). */
function fa_access_token() {
    $cache = coia_pasta_privada() . '/coia_token_cache.json';
    if (is_readable($cache)) {
        $c = json_decode((string)file_get_contents($cache), true);
        if (!empty($c['token']) && ($c['expira'] ?? 0) > time() + 60) return $c['token'];
    }
    $chave = fa_chave();
    $agora = time();
    $jwt = fa_jwt([
        'iss' => $chave['client_email'],
        'scope' => 'https://www.googleapis.com/auth/datastore',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $agora,
        'exp' => $agora + 3600,
    ]);
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = json_decode((string)curl_exec($ch), true);
    curl_close($ch);
    if (empty($resp['access_token'])) {
        throw new Exception('Não foi possível obter acesso ao banco.');
    }
    @file_put_contents($cache, json_encode([
        'token' => $resp['access_token'],
        'expira' => $agora + intval($resp['expires_in'] ?? 3600),
    ]));
    @chmod($cache, 0600);
    return $resp['access_token'];
}

/** Chamada autenticada à API do Firestore. */
function fa_http($metodo, $url, $corpo = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . fa_access_token(), 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($corpo !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corpo));
    $resp = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $dados = json_decode((string)$resp, true);
    if ($resp === false || $codigo >= 500) throw new Exception('Banco de dados indisponível.');
    if ($codigo >= 400 && $codigo !== 404) {
        error_log('CoIA Firestore ' . $codigo . ': ' . substr((string)$resp, 0, 500));
        throw new Exception('Erro ao acessar o banco de dados.');
    }
    return $dados;
}

function fa_valor($v) {
    if (is_bool($v)) return ['booleanValue' => $v];
    if (is_int($v)) return ['integerValue' => (string)$v];
    if (is_float($v)) return ['doubleValue' => $v];
    if (is_null($v)) return ['nullValue' => null];
    if (is_array($v)) {
        $ehLista = $v === [] || array_keys($v) === range(0, count($v) - 1);
        if ($ehLista) return ['arrayValue' => ['values' => array_map('fa_valor', $v)]];
        return ['mapValue' => ['fields' => fa_campos($v)]];
    }
    return ['stringValue' => (string)$v];
}

function fa_ler_valor($v) {
    if (array_key_exists('stringValue', $v)) return $v['stringValue'];
    if (array_key_exists('integerValue', $v)) return (int)$v['integerValue'];
    if (array_key_exists('doubleValue', $v)) return (float)$v['doubleValue'];
    if (array_key_exists('booleanValue', $v)) return $v['booleanValue'];
    if (array_key_exists('timestampValue', $v)) return $v['timestampValue'];
    if (array_key_exists('nullValue', $v)) return null;
    if (isset($v['arrayValue'])) return array_map('fa_ler_valor', $v['arrayValue']['values'] ?? []);
    if (isset($v['mapValue'])) return fa_ler_campos($v['mapValue']['fields'] ?? []);
    return null;
}

function fa_ler_campos($campos) {
    $r = [];
    foreach ($campos as $k => $v) $r[$k] = fa_ler_valor($v);
    return $r;
}

function fa_campos(array $dados) {
    $campos = [];
    foreach ($dados as $k => $v) $campos[$k] = fa_valor($v);
    return $campos;
}

/** Consulta com filtros de igualdade. Ex.: fa_query('coia_artigos', ['empresa_cnpj' => $cnpj]) */
function fa_query($colecao, array $iguais = []) {
    $filtros = [];
    foreach ($iguais as $campo => $valor) {
        $filtros[] = ['fieldFilter' => [
            'field' => ['fieldPath' => $campo],
            'op' => 'EQUAL',
            'value' => fa_valor($valor),
        ]];
    }
    $q = ['structuredQuery' => ['from' => [['collectionId' => $colecao]]]];
    if (count($filtros) === 1) $q['structuredQuery']['where'] = $filtros[0];
    if (count($filtros) > 1) $q['structuredQuery']['where'] = ['compositeFilter' => ['op' => 'AND', 'filters' => $filtros]];

    $resp = fa_http('POST', fa_base() . ':runQuery', $q);
    $docs = [];
    foreach ((array)$resp as $item) {
        if (!isset($item['document'])) continue;
        $d = fa_ler_campos($item['document']['fields'] ?? []);
        $d['id'] = basename($item['document']['name']);
        $docs[] = $d;
    }
    return $docs;
}

function fa_get($colecao, $id) {
    $resp = fa_http('GET', fa_base() . '/' . $colecao . '/' . rawurlencode($id));
    if (empty($resp['name'])) return null;
    $d = fa_ler_campos($resp['fields'] ?? []);
    $d['id'] = $id;
    return $d;
}

/** Cria ou substitui um documento com id escolhido. */
function fa_set($colecao, $id, array $dados) {
    return fa_http('PATCH', fa_base() . '/' . $colecao . '/' . rawurlencode($id), ['fields' => fa_campos($dados)]);
}

/** Altera só os campos informados de um documento existente. */
function fa_update($colecao, $id, array $dados) {
    $mask = implode('&', array_map(fn($k) => 'updateMask.fieldPaths=' . rawurlencode($k), array_keys($dados)));
    return fa_http('PATCH', fa_base() . '/' . $colecao . '/' . rawurlencode($id) . '?' . $mask . '&currentDocument.exists=true',
        ['fields' => fa_campos($dados)]);
}

function fa_delete($colecao, $id) {
    return fa_http('DELETE', fa_base() . '/' . $colecao . '/' . rawurlencode($id));
}
