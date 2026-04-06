<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat Agent — Teste Local</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .chat-wrapper {
            width: 100%;
            max-width: 720px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .chat-header {
            background: #1a1a2e;
            color: #fff;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chat-header .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #4ade80;
            flex-shrink: 0;
        }

        .chat-header h1 { font-size: 1rem; font-weight: 600; }
        .chat-header p  { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }

        .chat-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-size: 0.8rem;
            color: #856404;
        }

        .chat-box {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            display: flex;
            flex-direction: column;
            height: 500px;
            overflow: hidden;
        }

        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            scroll-behavior: smooth;
        }

        /* Estado inicial vazio */
        .messages-empty {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 0.875rem;
            text-align: center;
        }

        .message {
            display: flex;
            flex-direction: column;
            max-width: 80%;
            gap: 0.25rem;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .message.user    { align-self: flex-end; align-items: flex-end; }
        .message.assistant { align-self: flex-start; align-items: flex-start; }

        .message-bubble {
            padding: 0.65rem 1rem;
            border-radius: 18px;
            font-size: 0.9rem;
            line-height: 1.5;
            word-break: break-word;
        }

        .message.user .message-bubble {
            background: #1a1a2e;
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .message.assistant .message-bubble {
            background: #f1f5f9;
            color: #1e293b;
            border-bottom-left-radius: 4px;
        }

        .message-meta {
            font-size: 0.7rem;
            color: #94a3b8;
            padding: 0 0.25rem;
        }

        /* Loading / typing indicator */
        .typing-indicator {
            align-self: flex-start;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.65rem 1rem;
            background: #f1f5f9;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
        }

        .typing-indicator span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #94a3b8;
            animation: bounce 1.2s infinite ease-in-out;
        }

        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30%            { transform: translateY(-6px); }
        }

        /* Mensagem de erro inline */
        .error-message {
            align-self: center;
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            max-width: 90%;
            text-align: center;
            animation: fadeIn 0.2s ease;
        }

        /* Área de input */
        .chat-input-area {
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.25rem;
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
            background: #fff;
        }

        .chat-input-area textarea {
            flex: 1;
            resize: none;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
            font-family: inherit;
            line-height: 1.4;
            max-height: 120px;
            outline: none;
            transition: border-color 0.2s;
        }

        .chat-input-area textarea:focus { border-color: #1a1a2e; }
        .chat-input-area textarea:disabled { background: #f8fafc; }

        .btn-send {
            background: #1a1a2e;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.65rem 1.1rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
            flex-shrink: 0;
            line-height: 1;
        }

        .btn-send:hover:not(:disabled) { background: #2d2d5e; }
        .btn-send:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Footer de debug */
        .debug-bar {
            background: #fff;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-size: 0.75rem;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .debug-bar span { font-weight: 600; color: #1a1a2e; }
    </style>
</head>
<body>

<div class="chat-wrapper">

    <div class="chat-header">
        <div class="status-dot"></div>
        <div>
            <h1>Assistente Virtual</h1>
            <p>Ambiente de teste local — não usar em produção</p>
        </div>
    </div>

    <div class="chat-info">
        ⚠️ Esta interface é exclusiva para testes locais. Integre via <code>POST /api/chat/message</code> no seu site.
    </div>

    <div class="chat-box">
        <div class="messages" id="messages">
            <div class="messages-empty" id="empty-state">
                Envie uma mensagem para iniciar a conversa.
            </div>
        </div>

        <div class="chat-input-area">
            <textarea
                id="input"
                rows="1"
                placeholder="Digite sua mensagem..."
                maxlength="4000"
            ></textarea>
            <button class="btn-send" id="btn-send">Enviar</button>
        </div>
    </div>

    <div class="debug-bar">
        <div>UUID da conversa: <span id="debug-uuid">—</span></div>
        <div>Tenant: <span id="debug-tenant">—</span></div>
        <div>Tokens usados: <span id="debug-tokens">—</span></div>
        <div>Modelo: <span id="debug-model">—</span></div>
    </div>

</div>

<script>
(function () {
    'use strict';

    const API_URL         = '/api/chat/message';
    const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]').content;

    const messagesEl      = document.getElementById('messages');
    const inputEl         = document.getElementById('input');
    const btnSend         = document.getElementById('btn-send');
    const emptyState      = document.getElementById('empty-state');
    const debugUuid       = document.getElementById('debug-uuid');
    const debugTenant     = document.getElementById('debug-tenant');
    const debugTokens     = document.getElementById('debug-tokens');
    const debugModel      = document.getElementById('debug-model');

    /** Slug da empresa (multi-tenant). Deve existir na tabela `tenants` (veja TenantSeeder). */
    const TENANT_SLUG     = 'default';

    let conversationUuid  = null;
    let isLoading         = false;

    // -------------------------------------------------------------------------
    // Envio de mensagem
    // -------------------------------------------------------------------------

    async function sendMessage() {
        const text = inputEl.value.trim();
        if (!text || isLoading) return;

        setLoading(true);
        hideEmptyState();
        appendMessage('user', text);
        inputEl.value = '';
        resizeTextarea();

        const typingEl = showTyping();

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({
                    message:           text,
                    conversation_uuid: conversationUuid,
                    tenant_slug:       TENANT_SLUG,
                    source:            'web-test',
                }),
            });

            const data = await response.json();

            typingEl.remove();

            if (!data.success) {
                showError(data.error || 'Erro desconhecido.');
                return;
            }

            conversationUuid = data.data.conversation_uuid;
            updateDebugBar(data.data.reply, data.data);
            appendMessage('assistant', data.data.reply.content);

        } catch (err) {
            typingEl.remove();
            showError('Falha na comunicação com o servidor. Verifique sua conexão.');
            console.error('[Chat]', err);
        } finally {
            setLoading(false);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers de UI
    // -------------------------------------------------------------------------

    function appendMessage(role, content) {
        const wrapper = document.createElement('div');
        wrapper.className = `message ${role}`;

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        bubble.textContent = content;

        const meta = document.createElement('div');
        meta.className = 'message-meta';
        meta.textContent = role === 'user' ? 'Você' : 'Assistente';

        wrapper.appendChild(bubble);
        wrapper.appendChild(meta);
        messagesEl.appendChild(wrapper);
        scrollToBottom();
    }

    function showTyping() {
        const el = document.createElement('div');
        el.className = 'typing-indicator';
        el.innerHTML = '<span></span><span></span><span></span>';
        messagesEl.appendChild(el);
        scrollToBottom();
        return el;
    }

    function showError(message) {
        const el = document.createElement('div');
        el.className = 'error-message';
        el.textContent = '⚠️ ' + message;
        messagesEl.appendChild(el);
        scrollToBottom();
    }

    function setLoading(loading) {
        isLoading      = loading;
        inputEl.disabled = loading;
        btnSend.disabled = loading;
        btnSend.textContent = loading ? '...' : 'Enviar';
    }

    function hideEmptyState() {
        if (emptyState) emptyState.style.display = 'none';
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function updateDebugBar(reply, payload) {
        debugUuid.textContent   = conversationUuid || '—';
        debugTenant.textContent = payload?.tenant_slug || '—';
        debugModel.textContent  = reply.model || '—';
        debugTokens.textContent = reply.tokens?.total ? `${reply.tokens.total} total` : '—';
    }

    // -------------------------------------------------------------------------
    // Auto-resize do textarea
    // -------------------------------------------------------------------------

    function resizeTextarea() {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
    }

    // -------------------------------------------------------------------------
    // Event listeners
    // -------------------------------------------------------------------------

    btnSend.addEventListener('click', sendMessage);

    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    inputEl.addEventListener('input', resizeTextarea);

    // Foca no input ao carregar
    inputEl.focus();
})();
</script>

</body>
</html>
