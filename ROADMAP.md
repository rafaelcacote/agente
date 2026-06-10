# Roadmap — Agente conversacional para sites

Documento de planejamento, **status atual** e próximos passos do projeto.

**Objetivo final:** um agente embeddable em qualquer site, seguro, configurável por cliente (tenant) e pronto para produção.

---

## Status atual do projeto (jun/2026)

### O que já funciona (local e pronto para evoluir)

| Área | Status | Detalhe |
|------|--------|---------|
| API de chat | ✅ | `POST /api/chat/message`, `GET /api/chat/conversation/{uuid}/history` |
| OpenAI | ✅ | `ChatService` + `OpenAIService`, contexto de conversa, FAQ por regras |
| Multi-tenant | ✅ | Cada empresa = `Tenant` com slug, prompt e settings próprios |
| Widget embed | ✅ | `public/widget/agente.js` + demos em `/widget/demo` |
| CORS | ✅ | `config/cors.php` + origens por tenant em `settings.allowed_origins` |
| Segurança API | ✅ | API Key (`X-Agent-Key`), rate limit, headers de segurança |
| Painel admin | ✅ | `/admin` — dashboard, CRUD tenants, conversas |
| Banco local | ✅ | SQLite (`database/database.sqlite`) |

### Fases concluídas

| Fase | Nome | Status |
|------|------|--------|
| 1 | Widget embeddable | ✅ Concluída |
| 2 | CORS e origem | ✅ Concluída |
| 3 | Autenticação e rate limit | ✅ Concluída |
| 4 | Painel administrativo | ✅ Concluída |
| **5** | **Produção e deploy** | ⏳ **Próxima etapa (MVP)** |
| 6 | Evoluções (opcional) | ⏳ Pós-MVP |

### Onde paramos

1. **MVP funcional em máquina local** — dá para cadastrar tenants, gerar API Key, testar widget e conversas **sem produção**.
2. **Correção de bug no chat** (10/06/2026): histórico enviado à OpenAI vinha em ordem errada (`getContextMessages` conflitava com `orderBy` do relacionamento). Corrigido em `Conversation.php` + `lockForUpdate` no `ChatService`.
3. **Correção no admin** (10/06/2026): paginação usava view inexistente `pagination::simple-default` → trocado para `->links()` padrão do Laravel.
4. **Pendente para produção**: Fase 5 (deploy, PostgreSQL/MySQL, CI, `.env.production`, backups).

### Problemas conhecidos / atenção local

| Item | Situação |
|------|----------|
| Conversas antigas no admin | Mensagens gravadas **antes** do fix de contexto podem ter respostas repetidas — use **nova conversa** (↺ no widget) para testar |
| Testes PHPUnit | Ambiente precisa de extensão `pdo_sqlite` no PHP CLI; alguns testes podem falhar sem ela |
| `CHAT_REQUIRE_API_KEY` | Em local pode ficar `false`; tenant com API Key ainda exige chave no widget |
| Rotas `/chat/test` e `/widget/demo` | Bloqueadas quando `APP_ENV=production` |

---

## Guia rápido: cadastrar tenants (funciona em LOCAL)

**Não precisa de produção.** Tudo abaixo roda com `php artisan serve` na sua máquina.

### 1. Subir o projeto

```bash
composer install
cp .env.example .env   # se ainda não tiver
php artisan key:generate
php artisan migrate
php artisan db:seed --class=TenantSeeder
php artisan serve
```

### 2. Configurar `.env` mínimo

```env
OPENAI_API_KEY=sk-...

ADMIN_EMAIL=admin@agente.local
ADMIN_PASSWORD=sua_senha

DEFAULT_TENANT_API_KEY=ag_dev_local_change_in_production

CORS_ALLOWED_ORIGINS=http://localhost:8000,http://127.0.0.1:8000
```

### 3. Acessar o painel

- URL: `http://127.0.0.1:8000/admin`
- Login: `ADMIN_EMAIL` / `ADMIN_PASSWORD`

### 4. Criar um tenant (cliente / agente)

1. **Tenants → Novo tenant**
2. Preencher:
   - **Nome** — ex.: `Loja ABC`
   - **Slug** — ex.: `loja-abc` (vai em `data-tenant` no widget)
   - **System prompt** — personalidade e regras do assistente daquele cliente
   - **Domínios CORS** — uma URL por linha (em local: `http://127.0.0.1:8000`)
   - **Cor / saudação** — opcional para o widget
3. Salvar → **Gerar API Key** → copiar a chave (só aparece uma vez)
4. Copiar o **snippet de integração** na mesma tela

### 5. Colocar o widget no site

```html
<script
  src="http://127.0.0.1:8000/widget/agente.js"
  data-tenant="loja-abc"
  data-api-key="ag_sua_chave_gerada"
  data-greeting="Olá! Como posso ajudar?"
  async
></script>
```

- **Mesmo domínio** (ex.: demo em `/widget/demo`): funciona direto.
- **Outro domínio**: incluir a origem em `CORS_ALLOWED_ORIGINS` e em **Domínios CORS** do tenant.

### 6. Cada tenant = um agente separado

| O que é por tenant | Onde configura |
|--------------------|----------------|
| Comportamento (prompt) | Admin → tenant → System prompt |
| Domínios permitidos | Admin → Domínios CORS |
| API Key | Admin → Gerar / rotacionar |
| Conversas | Admin → Conversas (filtro por tenant) |
| Widget no site | `data-tenant` + `data-api-key` únicos |

O tenant `default` (seeder) é só para desenvolvimento/demo.

---

## Visão geral das fases

| Fase | Nome | Objetivo | Status |
|------|------|----------|--------|
| 1 | Widget embeddable | Chat no site do cliente | ✅ |
| 2 | CORS e origem | API em outros domínios | ✅ |
| 3 | Autenticação e rate limit | Proteger a API | ✅ |
| 4 | Painel admin | Gerir tenants e conversas | ✅ |
| 5 | Produção e observabilidade | Deploy, logs, monitoramento | ⏳ |
| 6 | Evoluções (opcional) | Streaming, filas, RAG, etc. | ⏳ |

---

## Fase 1 — Widget embeddable no site ✅

- [x] `public/widget/agente.js` (Shadow DOM, localStorage, `data-*`)
- [x] `/widget/demo` e `demo-externo.html`
- [x] README com snippet de integração

---

## Fase 2 — CORS e validação de origem ✅

- [x] `config/cors.php`, `CORS_ALLOWED_ORIGINS`
- [x] `tenants.settings.allowed_origins`
- [x] Middlewares `MergeChatCorsOrigins`, `ValidateTenantOrigin`

---

## Fase 3 — Autenticação, rate limit e hardening ✅

- [x] API Key por tenant (`api_key_hash`, `X-Agent-Key`)
- [x] Rate limit IP + tenant (`config/chat.php`)
- [x] `/chat/test` e `/widget/demo` bloqueados em produção
- [x] Sanitização de metadata, security headers
- [ ] Política de retenção de conversas (opcional)

---

## Fase 4 — Painel administrativo ✅

- [x] Login `/admin` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`)
- [x] Dashboard, CRUD tenants, conversas, encerrar conversa
- [x] Gerar/revogar API Key, snippet de embed
- [x] Paginação corrigida (`->links()` padrão)

**Rotas admin:** `routes/admin.php`  
**Controllers:** `app/Http/Controllers/Admin/*`

---

## Fase 5 — Produção, deploy e observabilidade ⏳ PRÓXIMA

### O que falta

- [ ] `.env.production.example` e `DEPLOY.md`
- [ ] PostgreSQL ou MySQL em produção
- [ ] `config:cache`, `route:cache`, `view:cache` no deploy
- [ ] Fila (`QUEUE_CONNECTION=database` ou Redis)
- [ ] HTTPS (Let's Encrypt / proxy)
- [ ] Health check enriquecido (`/up` + DB)
- [ ] Backups automáticos
- [ ] CI (GitHub Actions: test + Pint)
- [ ] Testes Feature do chat com mock OpenAI

### Critério de conclusão

- App deployada com widget em site real
- Testes no CI passando
- Logs e health check ok

---

## Fase 6 — Evoluções (opcional, pós-MVP)

- [ ] Streaming (SSE/WebSocket)
- [ ] Jobs assíncronos na fila
- [ ] Function calling
- [ ] RAG / base de conhecimento por tenant
- [ ] WhatsApp, analytics, export CSV

---

## Checklist consolidado

### Frontend / embed
- [x] Widget JS, demo, customização por tenant

### API / infra
- [x] CORS, origem, API Key, rate limit, rotas dev protegidas

### Admin
- [x] Login, CRUD tenants, conversas, snippet, paginação

### Qualidade
- [x] Teste unitário ordem do contexto (`ConversationContextTest`)
- [ ] Testes Feature chat + mock OpenAI
- [ ] CI

### Documentação
- [x] README (widget, CORS, API key, admin)
- [x] ROADMAP (este arquivo)
- [ ] Guia de deploy (Fase 5)

### Produção
- [ ] PostgreSQL/MySQL, deploy, backups

### Correções recentes
- [x] Bug contexto OpenAI fora de ordem (`Conversation::getContextMessages`)
- [x] Admin: `View [simple-default] not found` na paginação

---

## Ordem de implementação

```
Fase 1–4  ✅ MVP local completo
    ↓
Fase 5    ⏳ Deploy + testes + produção
    ↓
Fase 6    Evoluções conforme negócio
```

---

## Histórico

| Data | Marco | Notas |
|------|-------|-------|
| 2026-04-01 | Base | API + OpenAI + `/chat/test` |
| 2026-04-06 | Multi-tenant | `Tenant`, `tenant_slug`, seeder |
| 2026-06-10 | Fase 1 | Widget `agente.js`, demos |
| 2026-06-10 | Fase 2 | CORS + `allowed_origins` |
| 2026-06-10 | Fase 3 | API Key, rate limit, security |
| 2026-06-10 | Fase 4 | Painel `/admin` |
| 2026-06-10 | Hotfix | Ordem do contexto no chat; paginação admin |
| — | Fase 5 | **Próximo passo** |

---

## Referências no código

| Arquivo | Papel |
|---------|-------|
| `app/Services/Chat/ChatService.php` | Orquestração do fluxo |
| `app/Services/OpenAI/OpenAIService.php` | HTTP OpenAI |
| `app/Models/Conversation.php` | Contexto + `getContextMessages` |
| `app/Models/Tenant.php` | Multi-empresa, API Key, embed snippet |
| `public/widget/agente.js` | Widget no site |
| `routes/admin.php` | Painel administrativo |
| `config/openai.php` | Modelo, prompt, limites |
| `config/chat.php` | Rate limit, API key global |
| `config/cors.php` | CORS da API |
| `config/admin.php` | Credenciais do `/admin` |

---

## Comandos úteis

```bash
php artisan serve
php artisan migrate
php artisan db:seed --class=TenantSeeder
php artisan tenant:api-key {slug} --rotate   # gerar API Key via CLI
php artisan test
```

**URLs locais:**

| URL | Uso |
|-----|-----|
| `/admin` | Painel (tenants, conversas) |
| `/widget/demo` | Testar widget |
| `/chat/test` | UI de dev (só local) |
| `/api/chat/message` | API do agente |
