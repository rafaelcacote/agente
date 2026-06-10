# Agente

Backend em **Laravel** que expõe um **assistente de chat** integrado à **OpenAI**. Cada conversa é persistida no banco (SQLite por padrão), com histórico, tokens e metadados — pronto para evoluir para filas, autenticação ou outros canais (WhatsApp, app mobile, etc.).

---

## O que este projeto faz

- **API REST** para enviar mensagens e receber respostas da IA em tempo real.
- **Conversas com contexto**: as últimas mensagens da conversa são enviadas à API da OpenAI para manter coerência no diálogo (limite configurável).
- **Persistência**: modelos `Conversation` e `Message` guardam UUID público da conversa, origem (`web`, `api`, etc.), IP, user-agent, metadados opcionais e uso de tokens por resposta.
- **Camada de serviços**: `ChatService` orquestra fluxo e persistência; `OpenAIService` isola chamadas HTTP à OpenAI (`/v1/chat/completions`).
- **Widget embeddable**: script JS (`/widget/agente.js`) para integrar o chat em qualquer site com uma tag `<script>`.
- **Interface de teste**: rota web `/chat/test` para experimentar o fluxo localmente (em produção, proteja ou remova).

O **system prompt** padrão posiciona o modelo como assistente de atendimento ao cliente (tom profissional, sem inventar fatos, encaminhamento a humano quando necessário). Tudo isso pode ser ajustado via `.env`.

---

## Stack

| Camada        | Tecnologia                          |
|---------------|-------------------------------------|
| Framework     | Laravel 13, PHP 8.3+                |
| IA            | OpenAI Chat Completions API         |
| Banco         | SQLite (padrão), configurável       |

---

## Requisitos

- PHP **8.3+** com extensões usadas pelo Laravel
- [Composer](https://getcomposer.org/)
- Chave **OpenAI** (`OPENAI_API_KEY`)

---

## Instalação rápida

```bash
git clone https://github.com/rafaelcacote/agente.git
cd agente
composer install
cp .env.example .env
php artisan key:generate
```

Configure no `.env` pelo menos:

```env
OPENAI_API_KEY=sk-...
```

Opcionalmente ajuste `OPENAI_MODEL`, `OPENAI_SYSTEM_PROMPT`, `OPENAI_CONTEXT_LIMIT`, etc. (veja `config/openai.php`).

```bash
touch database/database.sqlite   # se usar SQLite e o arquivo não existir
php artisan migrate
php artisan serve
```

Abra `http://127.0.0.1:8000/widget/demo` para testar o widget, ou `http://127.0.0.1:8000/chat/test` para a UI de desenvolvimento.

---

## Widget no site (embed)

Adicione uma linha no HTML do site do cliente:

```html
<script
  src="https://api.seudominio.com/widget/agente.js"
  data-tenant="minha-empresa"
  data-position="bottom-right"
  data-primary-color="#1a1a2e"
  data-greeting="Olá! Como posso ajudar?"
  data-api-key="ag_sua_chave_aqui"
  async
></script>
```

### Atributos

| Atributo | Obrigatório | Descrição |
|----------|-------------|-----------|
| `data-tenant` | Sim | Slug do tenant cadastrado no banco (`tenants.slug`) |
| `data-api-url` | Não | URL do endpoint. Padrão: origem do script + `/api/chat/message` |
| `data-api-key` | Sim* | Chave pública do tenant (`X-Agent-Key`). *Obrigatória quando o tenant tem API Key |
| `data-position` | Não | `bottom-right` (padrão) ou `bottom-left` |
| `data-primary-color` | Não | Cor principal do widget (hex) |
| `data-greeting` | Não | Mensagem exibida quando o chat está vazio |

### Comportamento

- O widget usa **Shadow DOM** para não conflitar com o CSS do site hospedeiro.
- A `conversation_uuid` é salva em `localStorage` por tenant — ao recarregar a página, a conversa é retomada.
- Botão **↺** no header do chat inicia uma nova conversa.
- O campo `source` enviado à API é `widget`.

### Páginas de demonstração

| URL | Descrição |
|-----|-----------|
| `/widget/demo` | Página Laravel simulando um site com o widget |
| `/widget/demo-externo.html` | HTML estático (simula site do cliente) |

### CORS (domínios externos)

Para o widget funcionar em outro domínio, configure:

1. **Global** — no `.env`:

```env
CORS_ALLOWED_ORIGINS=https://loja.com,https://www.loja.com
```

2. **Por tenant** — em `tenants.settings`:

```json
{
  "allowed_origins": ["https://loja.com", "https://www.loja.com"]
}
```

Quando o tenant define `allowed_origins`, a API valida o header `Origin` (ou `Referer`). Origens do `.env` também são aceitas (útil para desenvolvimento).

### Segurança (API Key e rate limit)

```env
CHAT_REQUIRE_API_KEY=true          # true em produção
CHAT_RATE_LIMIT_PER_MINUTE=30      # por IP
CHAT_RATE_LIMIT_PER_DAY=500        # por tenant
DEFAULT_TENANT_API_KEY=ag_dev_...  # chave do tenant default (dev/seeder)
```

**Gerar ou rotacionar chave de um tenant:**

```bash
php artisan tenant:api-key minha-empresa --rotate
```

A chave é exibida uma única vez. Use no widget via `data-api-key` ou no header `X-Agent-Key`.

Rotas `/chat/test` e `/widget/demo` ficam **inacessíveis** quando `APP_ENV=production`.

### Painel administrativo (`/admin`)

```env
ADMIN_EMAIL=admin@agente.local
ADMIN_PASSWORD=sua_senha_forte
```

Acesse `http://127.0.0.1:8000/admin` para:

- Dashboard com métricas do dia (conversas, mensagens, tokens)
- CRUD de tenants (prompt, CORS, widget, API Key, snippet de embed)
- Listagem e detalhe de conversas (filtros, encerrar conversa)

---

## API

Base: `/api` (prefixo padrão do Laravel).

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/chat/message` | Envia mensagem; cria ou continua conversa via `conversation_uuid` |
| `GET` | `/api/chat/conversation/{uuid}/history` | Lista mensagens da conversa |

**Exemplo — nova mensagem**

```bash
curl -s -X POST http://127.0.0.1:8000/api/chat/message \
  -H "Content-Type: application/json" \
  -d '{"message": "Olá!"}'
```

**Exemplo — continuar conversa**

```bash
curl -s -X POST http://127.0.0.1:8000/api/chat/message \
  -H "Content-Type: application/json" \
  -d '{"message": "E quanto custa?", "conversation_uuid": "UUID_RETORNADO_ANTERIORMENTE"}'
```

Campos opcionais no body: `source`, `metadata` (objeto JSON).

---

## Estrutura relevante

```
app/
  Http/Controllers/ChatController.php
  Services/Chat/ChatService.php
  Services/OpenAI/OpenAIService.php
  Models/Conversation.php, Message.php, Tenant.php
public/widget/agente.js
resources/views/widget/demo.blade.php
config/openai.php
routes/api.php, web.php
ROADMAP.md
```

---

## Testes

```bash
composer test
# ou
php artisan test
```

---

## Licença

Este repositório segue a licença **MIT** do esqueleto Laravel (veja `composer.json` / arquivos de licença do framework).

---

Feito com Laravel e OpenAI — um ponto de partida sólido para um **agente conversacional** com histórico e API limpa.
