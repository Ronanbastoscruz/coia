# PLANO — CoIA versão 1

## Resumo da versão 1
- Só o supergerente (nível 1, título **"Editor chefe"**) usa.
- Entra **só pelo SSO** da API M3R (`index.php?ticket=`), com `session_name('COIA_SESS')` em todo PHP.
- Gera um artigo com IA: **quem chama a IA é sempre o PHP**, com a chave guardada em `privado/coia-ia.json`.
- Depois o editor revisa e edita o artigo e salva como rascunho.
- Para publicar no blog do cliente (EasyPage), a v1 sai com o botão **"Copiar HTML"**. O botão **"Publicar no blog"** é ligado quando o arquivo próprio no EasyPage (`coia-post.php`) estiver pronto.
- Tudo é gravado e filtrado por `empresa_cnpj`, que vem sempre da sessão.
- O navegador **não acessa o Firestore**: tudo passa pelo PHP. Por isso não existe `token.php` nem `firebase-manager.js` na v1.
- Já tem um limite simples de uso da IA: um limite por dia para cada empresa.

## Telas
1. **Entrada** (`index.php?ticket=`): não tem formulário, só "Entrando no CoIA...".
   - Erros: "Acesso só pelo painel M3R." e "Seu usuário não tem acesso ao CoIA nesta versão."
2. **Gerar artigo** (`gerar.php`): campos Cliente/blog, Tema (obrigatório), Palavras-chave (até 5), Tamanho (Curto ~500 / Médio ~900 / Longo ~1500) e Observações.
   - Rodapé: "Hoje você usou X de Y gerações."
   - Botão: **[Gerar artigo com IA]**
3. **Revisar** (`revisar.php?id=`): Título (até 60 caracteres), Resumo (até 160) e o corpo num editor simples (negrito, subtítulo, lista, link).
   - Botões: **[Salvar rascunho]**, **[Gerar de novo]** (pede confirmação) e **[Ir para publicar]**.
4. **Publicar** (`publicar.php?id=`): mostra a prévia do artigo.
   - Botões: **[Copiar HTML]**, **[Publicar no blog do cliente]** (só aparece quando o site estiver configurado) e **[Voltar e editar]**.
5. **Histórico** (`historico.php`): lista do mais novo para o mais antigo, com Data, Cliente, Título e Status (Rascunho/Publicado/Erro), e uma busca por título.
   - Botões: **[Abrir]** e **[Ver no blog]**.

Menu lateral no estilo do manager do agendador: Gerar artigo · Histórico · Sair. Os avisos usam o `m3r-alerts.js`.

**Mensagens principais:**
- "Escrevendo seu artigo... isso leva até 1 minuto. Não feche esta página."
- "Artigo gerado! Revise antes de publicar."
- "A IA não respondeu agora. Tente de novo em alguns minutos."
- "Você atingiu o limite de Y artigos por dia. Novas gerações liberam amanhã às 00h."
- "Não foi possível publicar no blog. O artigo continua salvo como rascunho no CoIA."
- "Sua sessão expirou. Entre de novo pelo painel M3R."

## Pastas e arquivos
```
coia/
  index.php              recebe o ticket SSO, confere permitidos + nível 1, cria sessão, "sair"
  _sessao.php            session_name COIA_SESS, cookie seguro, exige nível 1 + CNPJ, token CSRF
  _topo.php / _rodape.php layout Bootstrap 5 (estilo do agendador)
  gerar.php              formulário de geração
  revisar.php            edição do artigo
  publicar.php           prévia, Copiar HTML, Publicar
  historico.php          lista dos artigos da empresa
  api/gerar.php          CSRF + limite + trava, chama a IA, limpa o HTML, grava o rascunho
  api/salvar.php         confere o dono (CNPJ) e salva
  api/publicar.php       confere o dono e envia para o coia-post.php do site
  api/lib/firebase_admin.php  cópia ajustada do agendador (chave coia-firebase-admin.json)
  api/lib/ia.php         lê coia-ia.json e chama a IA (prompt fixo, max_tokens)
  api/lib/html_limpo.php lista branca de tags HTML
  api/lib/consumo.php    limite diário + trava de 30s (coleção coia_consumo)
  api/lib/seguranca.php  CSRF, cabeçalhos, artigo_do_dono($id)
  assets/coia.js, assets/coia.css, m3r-alerts.js
  upload.js, .gitignore  (já existem; conferir a lista BLOQUEADOS)

servidor /privado/ (fora do site, nunca no Git):
  coia-firebase-admin.json, coia-ia.json, coia_sso_permitidos.json, coia-sites.json (CNPJ → site + chave)

EasyPage (m3r-core-shared/painel/blog/coia-post.php)  — tarefa 12, só com sua autorização
  recebe o POST assinado (HMAC) e só faz INSERT em posts/tags, com prepared statements; devolve a URL
```

## Cuidados de segurança
1. Todo PHP começa com `session_name('COIA_SESS'); session_start();`. O `token.php` do agendador **não** faz isso, então ele não pode ser copiado como está.
2. Se `coia_sso_permitidos.json` não existir, o acesso é **recusado**. O agendador hoje libera nesse caso.
3. A v1 aceita só o nível 1. Qualquer outro nível destrói a sessão.
4. O cookie da sessão usa HttpOnly, Secure e SameSite=Lax.
5. A chave da IA só é lida pelo PHP. As mensagens de erro nunca mostram a chave nem a resposta crua da IA.
6. O `empresa_cnpj` vem **sempre da sessão**, nunca do formulário nem da URL. Toda leitura ou edição por ID confere se o artigo é da mesma empresa.
7. O token CSRF vai em todo POST, e as ações só aceitam POST.
8. O HTML que a IA devolve é tratado como perigoso e passa por uma lista branca de tags (p, h2, h3, ul, ol, li, strong, em, a). O título é tratado sempre como texto.
9. Limites da IA:
   - um número máximo de gerações por dia para cada empresa;
   - `max_tokens` fixo;
   - tema com até 300 caracteres;
   - uma geração a cada 30 segundos por usuário;
   - cada geração fica registrada em `coia_consumo` (e-mail, modelo, tokens, custo estimado).
10. Tempo de resposta: `set_time_limit(120)` e curl com timeout de 90s. Se os testes passarem de 60s, a geração é feita em 2 partes.
11. **Não usar** a API genérica `/controller/api/` do EasyPage, porque ela grava em qualquer tabela e monta o SQL juntando texto.
12. Arquivos novos de segredo entram no `.gitignore` **e** na lista `BLOQUEADOS`.

## Tarefas em ordem (estimativa)
| # | Tarefa | Horas |
|---|--------|------:|
| 1 | Base, upload.js, .gitignore, layout | 3 |
| 2 | Login SSO (index.php + _sessao.php) | 3 |
| 3 | firebase_admin ajustado + teste de gravar/ler | 2 |
| 4 | ia.php + prompt + teste de tempo | 4 |
| 5 | html_limpo.php + testes com HTML ruim | 2 |
| 6 | consumo.php (limite e trava) | 2 |
| 7 | gerar.php + api/gerar.php | 4 |
| 8 | revisar.php + api/salvar.php + editor | 4 |
| 9 | publicar.php com "Copiar HTML" | 2 |
| 10 | historico.php | 2 |
| 11 | Segurança: CSRF, cabeçalhos, teste com 2 CNPJs | 3 |
| 12 | coia-post.php no EasyPage + api/publicar.php | 6 |
| 13 | Testes no servidor e ajustes | 4 |
| | **Total** | **~41h** |

Sem a tarefa 12, são cerca de 35h para entregar a v1 com o botão "Copiar HTML".

## Fica para depois
- Acesso do gerente (nível 2).
- Imagem de capa (enviada pelo usuário ou feita pela IA).
- Escolher o tom de voz e o público, e guardar o tom de cada cliente.
- Agendar a publicação.
- SEO avançado.
- Histórico de versões do artigo.
- Duplicar artigo e filtros no histórico.
- Teto mensal de custo.
- Escolher entre várias IAs.
- Editar ou apagar no EasyPage um artigo já publicado.

## Perguntas para responder antes de começar
1. **Qual IA e qual modelo** (OpenAI, Claude...)? Quem cria a chave? Qual o **limite de gerações por dia** para cada empresa?
2. **O SSO devolve o CNPJ de quem?** Da M3R ou do cliente final? Se o editor chefe é da M3R e escreve para vários clientes, de onde vem a lista de clientes da tela "Gerar"?
3. **Como ligar o CNPJ ao site EasyPage** (domínio e banco)? Quem preenche o `coia-sites.json`?
4. **Posso criar o `coia-post.php` no `m3r-core-shared`?** Ele vale para todos os sites.
5. **projeto_id e tenant_id** do CoIA no portal M3R (para o `coia_sso_permitidos.json`).
6. **Qual autor** (`autor_id`) aparece nos posts? Por exemplo, "M3R Suporte".
7. O artigo entra no blog **já publicado ou como rascunho**? A tabela `posts` tem campo de status?
8. **Firebase:** é um projeto novo ou o mesmo do agendador, com as coleções começando por `coia_`?
