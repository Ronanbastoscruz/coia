# CoIA — Gerador de artigos com IA (M3R)

Gerador de artigos com IA para os blogs dos clientes da M3R.
Papéis: só supergerente (nível 1) e gerente (nível 2). Não tem profissional (3) nem cliente.

## Como falar comigo
- Sempre em português, com termos simples (não sou programador sênior).
- Se faltar algum dado, pergunte em vez de adivinhar.
- Antes de escrever código, explique o plano em poucas linhas.

## Tecnologia (mesmo modelo do Agendador M3R)
- PHP 8 puro (sem Composer) + JavaScript no navegador + Bootstrap 5.
- Hospedagem: m3r.com.br/coia (Hostinger + Cloudflare).
- Login: SOMENTE pela API M3R (`/api/sso/connect.php` → ticket → `coia/index.php?ticket=`).
  Modelo: pasta `C:\Users\ronan\Desktop\agendador` (index.php, manager/_sessao.php,
  api/auth/token.php, firebase-manager.js, api/lib/firebase_admin.php).

## Cuidados próprios do CoIA
- Mesmo domínio do agendador = mesma sessão. Em TODO arquivo PHP do CoIA:
  `session_name('COIA_SESS'); session_start();` (inclusive no token.php).
- A pasta `privado` do servidor é COMPARTILHADA (`/home/u838921894/domains/m3r.com.br/privado/`).
  Arquivos do CoIA lá sempre começam com `coia`: `coia-firebase-admin.json`, `coia-ia.json`,
  `coia_sso_permitidos.json`. Nunca usar `firebase-admin.json` ou `sso_permitidos.json` (são do agendador).
- Sem locais/filiais (não usar `id_local` nem `fa_locais_permitidos`), a não ser que eu peça.
- Tudo é separado por empresa: todo documento grava `empresa_cnpj` e toda consulta filtra por ele.

## Segredos: NUNCA versionar, NUNCA subir para o site
- `*firebase-adminsdk*.json` (chave de administrador do Firebase) → no servidor vai para
  `privado/coia-firebase-admin.json`.
- `coia-ia.json` (chave da IA: OpenAI/Claude/etc.) → no servidor vai para `privado/coia-ia.json`.
  Lida só pelo PHP. NUNCA no JavaScript, HTML ou URL.
- `upload.js` (tem a senha do servidor), `.env`, `.env.*`.
- Pasta `uploads/` (arquivos dos usuários) e pasta `privado_servidor/`.
- Tudo isso está no `.gitignore` e na lista `BLOQUEADOS` do `upload.js`.
  Ao criar um novo tipo de segredo, acrescentar nos DOIS lugares.

## Publicação no servidor (upload.js)
- `node upload.js` dentro de `C:\Users\ronan\Desktop\coia` (precisa de `npm install ssh2-sftp-client`).
- Envia a pasta para `/home/u838921894/domains/m3r.com.br/public_html/coia`, pulando a lista `BLOQUEADOS`.
- Chaves e o conteúdo de `privado_servidor/` vão para a pasta privada (fora do site).
  O script recusa gravar lá qualquer nome que não comece com `coia`.
- `APAGAR_DO_SITE`: arquivos antigos/de teste que devem sumir do site (começa vazia).
- O Cloudflare guarda .js e .css por 7 dias. Ao alterar um .js/.css, mude a versão no endereço
  onde ele é importado (ex.: `arquivo.js?v=20261031a`), senão o site continua com o antigo.

## Versionamento (Git / GitHub)
- Repositório PRIVADO: `github.com/ronancruzbastos-collab/coia`.
- Commits pequenos, um assunto por commit, mensagem clara em português
  (ex.: "cria tela de login pelo SSO").
- Antes de commitar, confira `git status`: nunca inclua chaves, senhas, `upload.js`, `.env` ou `uploads/`.
- Push para `origin/main` ao final de cada tarefa (mudanças grandes: branch `dev`).

## No fim de cada tarefa
Entregar upload + git num bloco só, pronto para copiar e colar no PowerShell:
```
cd C:\Users\ronan\Desktop\coia; node upload.js; git add -A; git status; git commit -m "mensagem"; git push
```
