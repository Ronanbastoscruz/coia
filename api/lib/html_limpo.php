<?php
/**
 * api/lib/html_limpo.php — deixa passar SÓ as tags seguras no texto do artigo.
 * Tudo que vem da IA ou do editor é tratado como perigoso.
 */

const COIA_TAGS_OK = ['p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'a', 'blockquote', 'br'];
// Tags que somem com todo o conteúdo de dentro
const COIA_TAGS_FORA = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
    'textarea', 'select', 'svg', 'math', 'noscript', 'template', 'head', 'title', 'meta', 'link'];

function coia_html_limpo($html) {
    $html = (string)$html;
    if (trim($html) === '') return '';

    $doc = new DOMDocument();
    $anterior = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="coia-raiz">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($anterior);

    $raiz = $doc->getElementById('coia-raiz');
    if (!$raiz) return '';

    $saida = '';
    foreach (iterator_to_array($raiz->childNodes) as $no) {
        $saida .= coia_html_no($no);
    }
    // h1 vira h2 (o título do artigo já é o h1 do blog), linhas vazias somem
    return trim(preg_replace('/(<p>\s*<\/p>)+/', '', $saida));
}

function coia_html_no(DOMNode $no) {
    if ($no instanceof DOMText) {
        return htmlspecialchars($no->nodeValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (!($no instanceof DOMElement)) return ''; // comentários etc.

    $tag = strtolower($no->nodeName);
    if (in_array($tag, COIA_TAGS_FORA, true)) return '';
    if ($tag === 'h1') $tag = 'h2';
    if (in_array($tag, ['h4', 'h5', 'h6'], true)) $tag = 'h3';
    if ($tag === 'div') $tag = 'p';

    $dentro = '';
    foreach (iterator_to_array($no->childNodes) as $filho) $dentro .= coia_html_no($filho);

    if (!in_array($tag, COIA_TAGS_OK, true)) return $dentro; // tag desconhecida: fica só o texto
    if ($tag === 'br') return '<br>';

    if ($tag === 'a') {
        $href = trim($no->getAttribute('href'));
        if (!preg_match('#^https?://#i', $href)) return $dentro; // sem link perigoso (javascript:, data: ...)
        return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="noopener nofollow" target="_blank">' . $dentro . '</a>';
    }
    return "<$tag>$dentro</$tag>";
}

/** Texto puro do artigo (para "Copiar texto" e contar palavras). */
function coia_html_texto($html) {
    $txt = preg_replace(['#</(p|h2|h3|li|blockquote)>#i', '#<br\s*/?>#i'], ["\n\n", "\n"], (string)$html);
    $txt = html_entity_decode(strip_tags($txt), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace("/\n{3,}/", "\n\n", $txt));
}

function coia_contar_palavras($html) {
    $partes = preg_split('/\s+/u', coia_html_texto($html), -1, PREG_SPLIT_NO_EMPTY);
    return count($partes);
}
