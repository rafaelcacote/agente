/**
 * Agente — Widget de chat embeddable
 *
 * Uso:
 *   <script
 *     src="https://seu-dominio.com/widget/agente.js"
 *     data-tenant="minha-empresa"
 *     data-position="bottom-right"
 *     data-primary-color="#1a1a2e"
 *     data-greeting="Olá! Como posso ajudar?"
 *     data-api-key="ag_sua_chave_aqui"
 *     async
 *   ></script>
 */
(function () {
    'use strict';

    var SCRIPT = document.currentScript;
    if (!SCRIPT) {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            if (scripts[i].src && scripts[i].src.indexOf('agente.js') !== -1) {
                SCRIPT = scripts[i];
                break;
            }
        }
    }

    if (!SCRIPT) {
        console.error('[Agente] Script tag não encontrado.');
        return;
    }

    var config = parseConfig(SCRIPT);
    if (!config.tenant) {
        console.error('[Agente] Atributo data-tenant é obrigatório.');
        return;
    }

    var storageKey = 'agente_conversation_' + config.tenant;
    var conversationUuid = localStorage.getItem(storageKey) || null;
    var isOpen = false;
    var isLoading = false;
    var historyLoaded = false;

    var host = document.createElement('div');
    host.id = 'agente-widget-host';
    host.setAttribute('aria-live', 'polite');
    document.body.appendChild(host);

    var shadow = host.attachShadow({ mode: 'open' });
    shadow.innerHTML = buildTemplate(config);

    var els = {
        launcher: shadow.getElementById('agente-launcher'),
        panel: shadow.getElementById('agente-panel'),
        closeBtn: shadow.getElementById('agente-close'),
        messages: shadow.getElementById('agente-messages'),
        emptyState: shadow.getElementById('agente-empty'),
        input: shadow.getElementById('agente-input'),
        sendBtn: shadow.getElementById('agente-send'),
        newChatBtn: shadow.getElementById('agente-new-chat'),
    };

    bindEvents();
    updateLauncherLabel();

    // -------------------------------------------------------------------------
    // Config
    // -------------------------------------------------------------------------

    function parseConfig(script) {
        var tenant = script.getAttribute('data-tenant') || '';
        var apiUrl = script.getAttribute('data-api-url') || '';
        var position = script.getAttribute('data-position') || 'bottom-right';
        var primaryColor = script.getAttribute('data-primary-color') || '#1a1a2e';
        var greeting = script.getAttribute('data-greeting') || 'Olá! Envie uma mensagem para iniciar.';
        var apiKey = script.getAttribute('data-api-key') || '';

        if (!apiUrl) {
            try {
                var origin = new URL(script.src).origin;
                apiUrl = origin + '/api/chat/message';
            } catch (e) {
                apiUrl = '/api/chat/message';
            }
        }

        var historyUrl = apiUrl.replace(/\/message\/?$/, '');

        return {
            tenant: tenant.trim(),
            apiUrl: apiUrl,
            historyUrl: historyUrl,
            apiKey: apiKey.trim(),
            position: position === 'bottom-left' ? 'bottom-left' : 'bottom-right',
            primaryColor: primaryColor,
            greeting: greeting,
        };
    }

    function buildHeaders() {
        var headers = {
            'Accept': 'application/json',
            'X-Tenant-Slug': config.tenant,
        };

        if (config.apiKey) {
            headers['X-Agent-Key'] = config.apiKey;
        }

        return headers;
    }

    // -------------------------------------------------------------------------
    // Template + estilos (isolados no Shadow DOM)
    // -------------------------------------------------------------------------

    function buildTemplate(cfg) {
        var align = cfg.position === 'bottom-left' ? 'left: 20px; right: auto;' : 'right: 20px; left: auto;';

        return (
            '<style>' +
            ':host, * { box-sizing: border-box; }' +
            '#agente-root { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.4; }' +
            '#agente-launcher {' +
            '  position: fixed; bottom: 20px; ' + align +
            '  width: 56px; height: 56px; border-radius: 50%; border: none; cursor: pointer;' +
            '  background: ' + cfg.primaryColor + '; color: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.2);' +
            '  display: flex; align-items: center; justify-content: center; z-index: 2147483000;' +
            '  transition: transform .2s, box-shadow .2s;' +
            '}' +
            '#agente-launcher:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(0,0,0,.25); }' +
            '#agente-launcher svg { width: 26px; height: 26px; fill: currentColor; }' +
            '#agente-panel {' +
            '  position: fixed; bottom: 88px; ' + align +
            '  width: 380px; max-width: calc(100vw - 40px); height: 520px; max-height: calc(100vh - 120px);' +
            '  background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,.18);' +
            '  display: none; flex-direction: column; overflow: hidden; z-index: 2147483001;' +
            '}' +
            '#agente-panel.open { display: flex; }' +
            '#agente-header {' +
            '  background: ' + cfg.primaryColor + '; color: #fff; padding: 14px 16px;' +
            '  display: flex; align-items: center; justify-content: space-between; gap: 8px;' +
            '}' +
            '#agente-header h2 { margin: 0; font-size: 15px; font-weight: 600; }' +
            '#agente-header p { margin: 2px 0 0; font-size: 11px; opacity: .85; }' +
            '#agente-header-actions { display: flex; gap: 4px; }' +
            '#agente-header-actions button {' +
            '  background: rgba(255,255,255,.15); border: none; color: #fff; cursor: pointer;' +
            '  width: 32px; height: 32px; border-radius: 8px; font-size: 16px; line-height: 1;' +
            '}' +
            '#agente-header-actions button:hover { background: rgba(255,255,255,.25); }' +
            '#agente-messages { flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px; background: #f8fafc; }' +
            '#agente-empty { text-align: center; color: #94a3b8; font-size: 13px; margin: auto; padding: 20px; }' +
            '.agente-msg { display: flex; flex-direction: column; max-width: 85%; gap: 3px; animation: agente-fade .2s ease; }' +
            '@keyframes agente-fade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }' +
            '.agente-msg.user { align-self: flex-end; align-items: flex-end; }' +
            '.agente-msg.assistant { align-self: flex-start; align-items: flex-start; }' +
            '.agente-bubble { padding: 9px 13px; border-radius: 16px; word-break: break-word; white-space: pre-wrap; }' +
            '.agente-msg.user .agente-bubble { background: ' + cfg.primaryColor + '; color: #fff; border-bottom-right-radius: 4px; }' +
            '.agente-msg.assistant .agente-bubble { background: #e2e8f0; color: #1e293b; border-bottom-left-radius: 4px; }' +
            '.agente-meta { font-size: 10px; color: #94a3b8; padding: 0 4px; }' +
            '.agente-typing { align-self: flex-start; display: flex; gap: 4px; padding: 9px 13px; background: #e2e8f0; border-radius: 16px; border-bottom-left-radius: 4px; }' +
            '.agente-typing span { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; animation: agente-bounce 1.2s infinite ease-in-out; }' +
            '.agente-typing span:nth-child(2) { animation-delay: .2s; }' +
            '.agente-typing span:nth-child(3) { animation-delay: .4s; }' +
            '@keyframes agente-bounce { 0%,60%,100% { transform: translateY(0); } 30% { transform: translateY(-5px); } }' +
            '.agente-error { align-self: center; background: #fee2e2; color: #b91c1c; border-radius: 8px; padding: 8px 12px; font-size: 12px; text-align: center; max-width: 95%; }' +
            '#agente-footer { border-top: 1px solid #e2e8f0; padding: 12px; display: flex; gap: 8px; align-items: flex-end; background: #fff; }' +
            '#agente-input { flex: 1; resize: none; border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 12px; font-family: inherit; font-size: 14px; max-height: 100px; outline: none; }' +
            '#agente-input:focus { border-color: ' + cfg.primaryColor + '; }' +
            '#agente-input:disabled { background: #f1f5f9; }' +
            '#agente-send { background: ' + cfg.primaryColor + '; color: #fff; border: none; border-radius: 10px; padding: 9px 14px; cursor: pointer; font-size: 13px; flex-shrink: 0; }' +
            '#agente-send:disabled { opacity: .5; cursor: not-allowed; }' +
            '@media (max-width: 480px) {' +
            '  #agente-panel { width: calc(100vw - 24px); bottom: 80px; left: 12px !important; right: 12px !important; height: 70vh; }' +
            '  #agente-launcher { bottom: 16px; right: 16px !important; left: auto !important; }' +
            '}' +
            '</style>' +
            '<div id="agente-root">' +
            '  <button id="agente-launcher" type="button" aria-label="Abrir chat" title="Abrir chat">' +
            '    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>' +
            '  </button>' +
            '  <div id="agente-panel" role="dialog" aria-label="Chat com assistente">' +
            '    <div id="agente-header">' +
            '      <div><h2>Assistente Virtual</h2><p>Estamos online</p></div>' +
            '      <div id="agente-header-actions">' +
            '        <button id="agente-new-chat" type="button" title="Nova conversa" aria-label="Nova conversa">↺</button>' +
            '        <button id="agente-close" type="button" title="Fechar" aria-label="Fechar">×</button>' +
            '      </div>' +
            '    </div>' +
            '    <div id="agente-messages">' +
            '      <div id="agente-empty">' + escapeHtml(cfg.greeting) + '</div>' +
            '    </div>' +
            '    <div id="agente-footer">' +
            '      <textarea id="agente-input" rows="1" placeholder="Digite sua mensagem..." maxlength="4000"></textarea>' +
            '      <button id="agente-send" type="button">Enviar</button>' +
            '    </div>' +
            '  </div>' +
            '</div>'
        );
    }

    // -------------------------------------------------------------------------
    // Eventos
    // -------------------------------------------------------------------------

    function bindEvents() {
        els.launcher.addEventListener('click', togglePanel);
        els.closeBtn.addEventListener('click', closePanel);
        els.sendBtn.addEventListener('click', sendMessage);
        els.newChatBtn.addEventListener('click', startNewConversation);

        els.input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        els.input.addEventListener('input', resizeInput);
    }

    function togglePanel() {
        isOpen = !isOpen;
        els.panel.classList.toggle('open', isOpen);
        updateLauncherLabel();

        if (isOpen && !historyLoaded) {
            loadHistoryIfNeeded();
        }

        if (isOpen) {
            setTimeout(function () { els.input.focus(); }, 100);
        }
    }

    function closePanel() {
        isOpen = false;
        els.panel.classList.remove('open');
        updateLauncherLabel();
    }

    function updateLauncherLabel() {
        els.launcher.setAttribute('aria-label', isOpen ? 'Fechar chat' : 'Abrir chat');
        els.launcher.setAttribute('title', isOpen ? 'Fechar chat' : 'Abrir chat');
    }

    function startNewConversation() {
        conversationUuid = null;
        historyLoaded = true;
        localStorage.removeItem(storageKey);
        clearMessages();
        showEmptyState(config.greeting);
        els.input.focus();
    }

    // -------------------------------------------------------------------------
    // API
    // -------------------------------------------------------------------------

    async function sendMessage() {
        var text = els.input.value.trim();
        if (!text || isLoading) return;

        setLoading(true);
        hideEmptyState();
        appendMessage('user', text);
        els.input.value = '';
        resizeInput();

        var typingEl = showTyping();

        try {
            var headers = buildHeaders();
            headers['Content-Type'] = 'application/json';

            var response = await fetch(config.apiUrl, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    message: text,
                    conversation_uuid: conversationUuid,
                    tenant_slug: config.tenant,
                    source: 'widget',
                }),
            });

            var data = await response.json();
            typingEl.remove();

            if (!data.success) {
                showError(data.error || 'Não foi possível enviar a mensagem.');
                return;
            }

            conversationUuid = data.data.conversation_uuid;
            localStorage.setItem(storageKey, conversationUuid);
            historyLoaded = true;
            appendMessage('assistant', data.data.reply.content);

        } catch (err) {
            typingEl.remove();
            showError('Falha na comunicação. Verifique sua conexão.');
            console.error('[Agente]', err);
        } finally {
            setLoading(false);
        }
    }

    async function loadHistoryIfNeeded() {
        if (!conversationUuid || historyLoaded) return;

        try {
            var url = config.historyUrl + '/conversation/' + conversationUuid + '/history';
            var response = await fetch(url, {
                headers: buildHeaders(),
            });

            if (!response.ok) {
                startNewConversation();
                return;
            }

            var data = await response.json();

            if (!data.success || !data.data.messages || data.data.messages.length === 0) {
                historyLoaded = true;
                return;
            }

            hideEmptyState();
            clearMessages();

            data.data.messages.forEach(function (msg) {
                if (msg.role === 'user' || msg.role === 'assistant') {
                    appendMessage(msg.role, msg.content);
                }
            });

            historyLoaded = true;
            scrollToBottom();

        } catch (err) {
            console.warn('[Agente] Não foi possível carregar histórico.', err);
            historyLoaded = true;
        }
    }

    // -------------------------------------------------------------------------
    // UI helpers
    // -------------------------------------------------------------------------

    function appendMessage(role, content) {
        var wrapper = document.createElement('div');
        wrapper.className = 'agente-msg ' + role;

        var bubble = document.createElement('div');
        bubble.className = 'agente-bubble';
        bubble.textContent = content;

        var meta = document.createElement('div');
        meta.className = 'agente-meta';
        meta.textContent = role === 'user' ? 'Você' : 'Assistente';

        wrapper.appendChild(bubble);
        wrapper.appendChild(meta);
        els.messages.appendChild(wrapper);
        scrollToBottom();
    }

    function showTyping() {
        var el = document.createElement('div');
        el.className = 'agente-typing';
        el.innerHTML = '<span></span><span></span><span></span>';
        els.messages.appendChild(el);
        scrollToBottom();
        return el;
    }

    function showError(message) {
        var el = document.createElement('div');
        el.className = 'agente-error';
        el.textContent = message;
        els.messages.appendChild(el);
        scrollToBottom();
    }

    function setLoading(loading) {
        isLoading = loading;
        els.input.disabled = loading;
        els.sendBtn.disabled = loading;
        els.sendBtn.textContent = loading ? '...' : 'Enviar';
    }

    function hideEmptyState() {
        if (els.emptyState) els.emptyState.style.display = 'none';
    }

    function showEmptyState(text) {
        if (!els.emptyState) {
            els.emptyState = document.createElement('div');
            els.emptyState.id = 'agente-empty';
            els.messages.appendChild(els.emptyState);
        }
        els.emptyState.textContent = text;
        els.emptyState.style.display = 'block';
    }

    function clearMessages() {
        var children = els.messages.children;
        for (var i = children.length - 1; i >= 0; i--) {
            if (children[i].id !== 'agente-empty') {
                els.messages.removeChild(children[i]);
            }
        }
    }

    function scrollToBottom() {
        els.messages.scrollTop = els.messages.scrollHeight;
    }

    function resizeInput() {
        els.input.style.height = 'auto';
        els.input.style.height = Math.min(els.input.scrollHeight, 100) + 'px';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
