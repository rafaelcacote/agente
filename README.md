# Agente

Backend em **Laravel** que expõe um **assistente de chat** integrado à **OpenAI**. Cada conversa é persistida no banco (SQLite por padrão), com histórico, tokens e metadados — pronto para evoluir para filas, autenticação ou outros canais (WhatsApp, app mobile, etc.).

---

## O que este projeto faz

- **API REST** para enviar mensagens e receber respostas da IA em tempo real.
- **Conversas com contexto**: as últimas mensagens da conversa são enviadas à API da OpenAI para manter coerência no diálogo (limite configurável).
- **Persistência**: modelos `Conversation` e `Message` guardam UUID público da conversa, origem (`web`, `api`, etc.), IP, user-agent, metadados opcionais e uso de tokens por resposta.
- **Camada de serviços**: `ChatService` orquestra fluxo e persistência; `OpenAIService` isola chamadas HTTP à OpenAI (`/v1/chat/completions`).
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

Abra `http://127.0.0.1:8000/chat/test` para testar a UI, ou use a API abaixo.

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
  Models/Conversation.php, Message.php
config/openai.php
routes/api.php, web.php
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
