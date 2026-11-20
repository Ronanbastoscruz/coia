# PLANO — CoIA versão 1

## Resumo da versão 1
- O CoIA é um **SaaS independente**: não tem ligação com o EasyPage nem com outros sistemas. Os conectores ficam para depois.
- **Uma conta por cliente**: cada cliente entra com a própria conta e gera os próprios artigos. Ele é o supergerente (nível 1, título **"Editor chefe"**) da empresa dele.
- O acesso é **só pelo SSO** da API M3R (`index.php?ticket=`), com `session_name('COIA_SESS')` em todo arquivo PHP.
- O fluxo é: **cadastro do cliente** (dados da empresa) → **gerar artigo com IA** → **revisar e editar** → artigo salvo, depois copiado ou baixado.
- **Duas IAs**: OpenAI e Grok (xAI). Quem chama a IA é sempre o PHP, com as chaves em `privado/coia-ia.json`.
- O **autor** do artigo é o nome do cliente que está no cadastro.
- **Firebase: projeto novo**, só do CoIA (`privado/coia-firebase-admin.json`). O navegador não acessa o Firestore direto: tudo passa pelo PHP.
- Tudo é gravado e filtrado por `empresa_cnpj`, que vem sempre da sessão.

## Telas
1. **Entrada** (`index.php?ticket=`): não tem formulário. Mostra "Entrando no CoIA...".
   - Erros: "Acesso só pelo painel M3R." e "Seu usuário não tem acesso ao CoIA nesta versão."
2. **Cadastro do cliente** (`cadastro.php`): é a primeira tela depois do primeiro acesso.
   - Campos: Nome da empresa (aparece como autor dos artigos), Site, Ramo/segmento, Cidade, Público-alvo e Tom de voz (Profissional/Amigável/Descontraído/Técnico).
   - Botão: **[Salvar cadastro]**. Esses dados entram no pedido à IA para o texto sair com a cara do cliente.
3. **Gerar artigo** (`gerar.php`):
   - Campos: Tema (obrigatório), Palavras-chave (até 5), Tamanho (Curto ~500 / Médio ~900 / Longo ~1500), IA (OpenAI / Grok) e Observações.
   - Rodapé: "Hoje você usou X de Y gerações."
   - Botão: **[Gerar artigo com IA]**
4. **Revisar** (`revisar.php?id=`): Título (até 60 caracteres), Resumo (até 160) e o corpo num editor simples (negrito, subtítulo, lista, link).
   - Botões: **[Salvar]**, **[Gerar de novo]** (pede confirmação), **[Copiar HTML]**, **[Copiar texto]** e **[Baixar (.html)]**.
5. **Meus artigos** (`historico.php`): lista do mais novo para o mais antigo, com Data, Título, IA usada e Status (Rascunho/Pronto). Tem busca por título.
   - Botões: **[Abrir]** e **[Excluir]** (pede confirmação).

O menu lateral segue o estilo do manager do agendador: Gerar artigo · Meus artigos · Cadastro · Sair. Os avisos usam o `m3r-alerts.js`.

**Mensagens principais:**
- "Escrevendo seu artigo... isso leva até 1 minuto. Não feche esta página."
- "Artigo gerado! Revise antes de usar."
- "A IA não respondeu agora. Tente de novo em alguns minutos." (essa falha não conta no limite)
- "Você atingiu o limite de Y artigos por dia. Novas gerações liberam amanhã às 00h."
- "Complete seu cadastro antes de gerar o primeiro artigo."
- "Sua sessão expirou. Entre de novo pelo painel M3R."

## Pastas e arquivos
```
coia/
  index.php              recebe o ticket SSO, confere permitidos + nível 1, cria a sessão, "sair"
  _sessao.php            session_name COIA_SESS, cookie seguro, exige nível 1 + CNPJ, token CSRF
  _topo.php / _rodape.php layout Bootstrap 5 (estilo do agendador)
  cadastro.php           dados do cliente
  gerar.php              formulário de geração
  revisar.php            edição + copiar/baixar
  historico.php          "Meus artigos"
  api/cadastro.php       salva o cadastro (coleção coia_clientes, id = CNPJ da sessão)
  api/gerar.php          CSRF + limite + trava, chama a IA, limpa o HTML, grava o artigo
  api/salvar.php         confere o dono (CNPJ) e salva
  api/excluir.php        confere o dono e exclui
  api/lib/firebase_admin.php  cópia ajustada do agendador (chave coia-firebase-admin.json)
  api/lib/ia.php         escolhe OpenAI ou Grok, lê coia-ia.json, prompt fixo, max_tokens
  api/lib/html_limpo.php lista branca de tags HTML
  api/lib/consumo.php    limite diário + trava de 30s (coleção coia_consumo)
  api/lib/seguranca.php  CSRF, cabeçalhos, artigo_do_dono($id)
  assets/coia.js, assets/coia.css, m3r-alerts.js
  upload.js, .gitignore  (já existem; conferir a lista BLOQUEADOS)

servidor /privado/ (fora do site, nunca no Git):
  coia-firebase-admin.json   chave do Firebase NOVO do CoIA
  coia-ia.json               chaves OpenAI + Grok, modelo de cada uma, limite por dia
  coia_sso_permitidos.json   projeto_id / tenant_id do CoIA no portal M3R
```

**Coleções no Firestore (projeto novo):**
- `coia_clientes`: o cadastro, com 1 documento por CNPJ.
- `coia_artigos`: empresa_cnpj, título, resumo, corpo, palavras-chave, IA, modelo, status, autor, datas.
- `coia_consumo`: empresa_cnpj, e-mail, IA, modelo, tokens, custo estimado, data e status.

**Exemplo de `coia-ia.json`** (só a estrutura, sem chaves de verdade):
```json
{
  "padrao": "openai",
  "openai": { "chave": "...", "modelo": "..." },
  "grok":   { "chave": "...", "modelo": "...", "url": "https://api.x.ai/v1/chat/completions" },
  "limite_dia_empresa": 10,
  "max_tokens": 4000
}
```
O Grok usa o mesmo formato de chamada da OpenAI. Por isso o `ia.php` usa uma única função para as duas, mudando só o endereço, a chave e o modelo.

## Cuidados de segurança
1. Todo PHP começa com `session_name('COIA_SESS'); session_start();`. O `token.php` do agendador **não** faz isso, então ele não pode ser copiado como está. Na v1 nem é preciso, porque o navegador não acessa o Firestore.
2. Se `coia_sso_permitidos.json` não existir, o acesso é **recusado**. O agendador hoje libera nesse caso.
3. A v1 aceita só o nível 1. Qualquer outro nível destrói a sessão.
4. O cookie da sessão usa HttpOnly, Secure e SameSite=Lax.
5. As chaves da IA só são lidas pelo PHP. As mensagens de erro nunca mostram a chave nem a resposta crua da IA.
6. O `empresa_cnpj` vem **sempre da sessão**, nunca do formulário nem da URL. Toda leitura, edição ou exclusão por ID confere se o artigo é da mesma empresa.
7. O token CSRF vai em todo POST, e as ações só aceitam POST.
8. O HTML que a IA devolve é tratado como perigoso e passa por uma lista branca de tags (p, h2, h3, ul, ol, li, strong, em, a). O título e os dados do cadastro são tratados sempre como texto.
9. Limites da IA:
   - um número máximo de gerações por dia para cada empresa;
   - `max_tokens` fixo;
   - tema com até 300 caracteres;
   - uma geração a cada 30 segundos;
   - cada geração fica registrada em `coia_consumo`.
10. Tempo de resposta: `set_time_limit(120)` e curl com timeout de 90s. Se os testes passarem de 60s, a geração é feita em 2 partes.
11. Arquivos novos de segredo entram no `.gitignore` **e** na lista `BLOQUEADOS`.

## Tarefas em ordem (estimativa)
| # | Tarefa | Horas |
|---|--------|------:|
| 1 | Base, upload.js, .gitignore, layout | 3 |
| 2 | Login SSO (index.php + _sessao.php) | 3 |
| 3 | firebase_admin no projeto novo + teste de gravar/ler | 2 |
| 4 | Cadastro do cliente (tela + api) | 3 |
| 5 | ia.php com OpenAI e Grok + prompt + teste de tempo | 5 |
| 6 | html_limpo.php + testes com HTML ruim | 2 |
| 7 | consumo.php (limite e trava) | 2 |
| 8 | gerar.php + api/gerar.php | 4 |
| 9 | revisar.php + api/salvar.php + editor + copiar/baixar | 5 |
| 10 | historico.php + excluir | 2 |
| 11 | Segurança: CSRF, cabeçalhos, teste com 2 contas | 3 |
| 12 | Testes no servidor e ajustes | 4 |
| | **Total** | **~38h** |

## Fica para depois
- **Conectores**: publicar no blog EasyPage (com o `coia-post.php` seguro no `m3r-core-shared`), API com autenticação e outros sites.
- Acesso do gerente (nível 2).
- Imagem de capa feita pela IA.
- Agendar publicação.
- SEO avançado.
- Versões do artigo.
- Teto de custo mensal.

## O que você já respondeu
- **IA**: OpenAI e Grok.
- **Contas**: 1 para 1, cada cliente tem a própria conta.
- **EasyPage e integração**: ficam para depois.
- **Autor**: o nome do cliente no cadastro.
- **Firebase**: projeto novo.
- **Arquivos no EasyPage**: autorizado criar o que precisar, quando chegar a hora dos conectores.

- **Limite**: 5 gerações por dia por cliente (`limite_dia_empresa: 5`).
- **Chaves da IA**: o Ronan cria (OpenAI e Grok), depois de aprovar o visual.
- **Firebase**: projeto `coia-13e2d` ("Coia"), Firestore em southamerica-east1, regras fechando todo acesso pelo navegador (só o PHP acessa). A chave fica na pasta `coia` (fora do Git) e o `upload.js` leva para `privado/coia-firebase-admin.json`.
- **Cadastro do cliente**: só os campos da tela 2.
- **API M3R / SSO**: fica para mais tarde. Até lá, o login de teste é decidido na hora de começar o código.

## Protótipo visual
- Arquivo: `prototipo/index.html`. Só tem dados de exemplo, não tem lógica e **não sobe para o site** (está bloqueado no `upload.js`).
- Serve para aprovar o visual antes do código de verdade.
