// assets/coia.js — comportamento das telas do CoIA (sem script embutido nas páginas).
(function () {
  'use strict';

  const $ = (id) => document.getElementById(id);
  const csrf = document.querySelector('meta[name="csrf"]')?.content || '';

  // Avisos: usa o m3r-alerts.js (com o texto escapado)
  function esc(t) { const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
  const aviso = (msg, tipo) => window.m3rAlert(esc(msg), tipo || 'info');
  const confirmar = (msg) => window.m3rConfirm(esc(msg));

  // Chamada às ações do servidor (sempre POST + token CSRF)
  async function api(acao, dados) {
    let resp;
    try {
      resp = await fetch('api/' + acao + '.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify(dados || {}),
        credentials: 'same-origin',
      });
    } catch (e) {
      throw new Error('Sem conexão. Verifique sua internet e tente de novo.');
    }
    let json = {};
    try { json = await resp.json(); } catch (e) { /* resposta vazia */ }
    if (resp.status === 401 && json.sessao === false) {
      await aviso(json.erro || 'Sua sessão expirou. Entre de novo pelo painel M3R.', 'warning');
      location.href = 'index.php';
      throw new Error('');
    }
    if (!resp.ok || !json.ok) {
      const err = new Error(json.erro || 'Não foi possível concluir agora. Tente de novo em instantes.');
      err.ir = json.ir;
      throw err;
    }
    return json;
  }

  function carregando(btn, sim) {
    if (!btn) return;
    if (sim) { btn.dataset.html = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Aguarde...'; }
    else { btn.disabled = false; if (btn.dataset.html) btn.innerHTML = btn.dataset.html; }
  }

  // Menu no celular
  $('btnMenu')?.addEventListener('click', () => $('lateral').classList.toggle('aberta'));

  // ───────── Cadastro ─────────
  const formCad = $('formCadastro');
  if (formCad) {
    formCad.addEventListener('submit', async (e) => {
      e.preventDefault();
      const f = new FormData(formCad);
      if (!String(f.get('nome')).trim() || !String(f.get('ramo')).trim()) { aviso('Preencha o nome da empresa e o ramo.', 'warning'); return; }
      const btn = formCad.querySelector('button[type=submit]');
      carregando(btn, true);
      try {
        await api('cadastro', Object.fromEntries(f));
        await aviso('Cadastro salvo.', 'success');
        location.href = 'gerar.php';
      } catch (err) { if (err.message) aviso(err.message, 'danger'); }
      finally { carregando(btn, false); }
    });
  }

  // ───────── Gerar (usado no formulário e no "Gerar de novo") ─────────
  async function gerar(dados, btn) {
    const caixa = $('gerando');
    carregando(btn, true);
    caixa?.classList.remove('d-none');
    try {
      const r = await api('gerar', dados);
      location.href = 'revisar.php?id=' + encodeURIComponent(r.id) + '&novo=1';
    } catch (err) {
      caixa?.classList.add('d-none');
      carregando(btn, false);
      if (err.message) await aviso(err.message, 'danger');
      if (err.ir) location.href = err.ir;
    }
  }

  const formGerar = $('formGerar');
  if (formGerar) {
    formGerar.addEventListener('submit', (e) => {
      e.preventDefault();
      const f = Object.fromEntries(new FormData(formGerar));
      if (!String(f.tema || '').trim()) { aviso('Preencha o tema do artigo.', 'warning'); return; }
      gerar(f, $('btnGerar'));
    });
  }

  // ───────── Revisar ─────────
  const art = $('artigo');
  if (art) {
    const corpo = $('r-corpo');
    let alterado = false;
    ['r-titulo', 'r-resumo'].forEach((id) => $(id).addEventListener('input', () => { alterado = true; }));
    corpo.addEventListener('input', () => { alterado = true; contarPalavras(); });
    window.addEventListener('beforeunload', (e) => { if (alterado) { e.preventDefault(); e.returnValue = ''; } });

    if (new URLSearchParams(location.search).has('novo')) {
      aviso('Artigo gerado! Revise antes de usar.', 'success');
      history.replaceState(null, '', 'revisar.php?id=' + encodeURIComponent(art.dataset.id));
    }

    // Contadores de caracteres
    document.querySelectorAll('[data-contador]').forEach((el) => {
      const campo = $(el.dataset.contador), max = +el.dataset.max;
      const atualizar = () => {
        const n = campo.value.length;
        el.textContent = n + '/' + max;
        el.classList.toggle('text-danger', n > max);
      };
      campo.addEventListener('input', atualizar); atualizar();
    });

    function contarPalavras() {
      const n = (corpo.innerText.trim().match(/\S+/g) || []).length;
      $('contaPalavras').textContent = n;
    }

    // Barra do editor
    document.querySelectorAll('.editor-barra [data-cmd]').forEach((b) => {
      b.addEventListener('mousedown', (e) => e.preventDefault()); // não perde a seleção
      b.addEventListener('click', () => {
        corpo.focus();
        const cmd = b.dataset.cmd;
        if (cmd === 'h2' || cmd === 'p') document.execCommand('formatBlock', false, cmd);
        else if (cmd === 'link') {
          const url = prompt('Endereço do link (começando com https://):', 'https://');
          if (url && /^https?:\/\//i.test(url)) document.execCommand('createLink', false, url);
        } else document.execCommand(cmd, false, null);
        alterado = true;
      });
    });

    async function salvar(status, btn) {
      carregando(btn, true);
      try {
        const r = await api('salvar', {
          id: art.dataset.id,
          titulo: $('r-titulo').value,
          resumo: $('r-resumo').value,
          corpo_html: corpo.innerHTML,
          status: status,
        });
        corpo.innerHTML = r.corpo_html; // versão limpa pelo servidor
        alterado = false;
        const badge = $('badgeStatus');
        badge.textContent = r.status === 'pronto' ? 'Pronto' : 'Rascunho';
        badge.className = 'badge ' + (r.status === 'pronto' ? 'text-bg-success' : 'text-bg-secondary');
        aviso(r.status === 'pronto' ? 'Artigo salvo como pronto.' : 'Rascunho salvo.', 'success');
      } catch (err) { if (err.message) aviso(err.message, 'danger'); }
      finally { carregando(btn, false); }
    }
    $('btnSalvar').addEventListener('click', (e) => salvar('rascunho', e.currentTarget));
    $('btnPronto').addEventListener('click', (e) => salvar('pronto', e.currentTarget));

    $('btnRegerar').addEventListener('click', async (e) => {
      const btn = e.currentTarget;
      if (!(await confirmar('O texto atual será substituído por um novo. Isso conta como 1 geração do dia. Continuar?'))) return;
      alterado = false;
      const d = art.dataset;
      gerar({ artigo_id: d.id, tema: d.tema, palavras_chave: d.palavras, tamanho: d.tamanho, ia: d.ia, observacoes: d.obs }, btn);
    });

    // Copiar / baixar
    async function copiar(texto, msg) {
      try { await navigator.clipboard.writeText(texto); aviso(msg, 'success'); }
      catch (e) { aviso('Não foi possível copiar. Selecione o texto e use Ctrl+C.', 'warning'); }
    }
    const htmlCompleto = () => '<h1>' + esc($('r-titulo').value) + '</h1>\n' + corpo.innerHTML;
    $('btnCopiarHtml').addEventListener('click', () => copiar(htmlCompleto(), 'HTML copiado!'));
    $('btnCopiarTexto').addEventListener('click', () => copiar($('r-titulo').value + '\n\n' + corpo.innerText.trim(), 'Texto copiado!'));
    $('btnBaixar').addEventListener('click', () => {
      const titulo = $('r-titulo').value || 'artigo';
      const pagina = '<!DOCTYPE html>\n<html lang="pt-BR">\n<head>\n<meta charset="UTF-8">\n<title>' + esc(titulo) + '</title>\n'
        + '<meta name="description" content="' + esc($('r-resumo').value) + '">\n</head>\n<body>\n' + htmlCompleto() + '\n</body>\n</html>\n';
      const nome = titulo.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'artigo';
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([pagina], { type: 'text/html' }));
      a.download = nome + '.html';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    });
  }

  // ───────── Meus artigos: excluir ─────────
  document.querySelectorAll('[data-excluir]').forEach((b) => {
    b.addEventListener('click', async () => {
      if (!(await confirmar('Excluir este artigo? Isso não pode ser desfeito.'))) return;
      carregando(b, true);
      try { await api('excluir', { id: b.dataset.excluir }); b.closest('tr').remove(); aviso('Artigo excluído.', 'success'); }
      catch (err) { carregando(b, false); if (err.message) aviso(err.message, 'danger'); }
    });
  });
})();
