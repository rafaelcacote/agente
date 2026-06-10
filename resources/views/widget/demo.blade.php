<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo — Widget Agente</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1e293b;
        }

        .page {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem 1.5rem 6rem;
        }

        .hero {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0,0,0,.12);
            margin-bottom: 2rem;
        }

        .hero h1 {
            font-size: 1.75rem;
            margin-bottom: .5rem;
            color: #1a1a2e;
        }

        .hero p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            font-size: .75rem;
            font-weight: 600;
            padding: .25rem .75rem;
            border-radius: 999px;
            margin-bottom: 1rem;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 16px rgba(0,0,0,.08);
            margin-bottom: 1.5rem;
        }

        .card h2 {
            font-size: 1rem;
            margin-bottom: .75rem;
            color: #1a1a2e;
        }

        .card p, .card li {
            font-size: .9rem;
            color: #64748b;
            line-height: 1.6;
        }

        .card ul {
            padding-left: 1.25rem;
            margin-top: .5rem;
        }

        pre {
            background: #1e293b;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            font-size: .8rem;
            overflow-x: auto;
            line-height: 1.5;
        }

        code { font-family: 'SF Mono', Consolas, monospace; }

        .hint {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 1rem;
            font-size: .85rem;
            color: #92400e;
            margin-top: 1rem;
        }

        .links {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 1rem;
        }

        .links a {
            color: #4f46e5;
            font-size: .875rem;
            text-decoration: none;
        }

        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="page">
    <div class="hero">
        <span class="badge">Widget embeddable + API Key</span>
        <h1>Site de demonstração</h1>
        <p>
            Esta página simula um site qualquer com o widget de chat integrado.
            Clique no botão flutuante no canto inferior direito para testar o agente.
        </p>
        <div class="links">
            <a href="{{ url('/widget/demo-externo.html') }}">Demo HTML estática →</a>
            <a href="{{ url('/chat/test') }}">Interface de teste antiga →</a>
            <a href="{{ url('/') }}">Início →</a>
        </div>
    </div>

    <div class="card">
        <h2>Como este widget foi integrado</h2>
        <pre><code>&lt;script
  src="{{ url('/widget/agente.js') }}"
  data-tenant="default"
  data-position="bottom-right"
  data-primary-color="#1a1a2e"
  data-greeting="Olá! Como posso ajudar você hoje?"
  data-api-key="sua-api-key"
  async
&gt;&lt;/script&gt;</code></pre>
    </div>

    <div class="card">
        <h2>Atributos disponíveis</h2>
        <ul>
            <li><code>data-tenant</code> — slug do tenant (obrigatório)</li>
            <li><code>data-api-url</code> — URL do endpoint (opcional; padrão: origem do script + <code>/api/chat/message</code>)</li>
            <li><code>data-position</code> — <code>bottom-right</code> ou <code>bottom-left</code></li>
            <li><code>data-primary-color</code> — cor principal do widget</li>
            <li><code>data-greeting</code> — mensagem exibida quando não há mensagens</li>
            <li><code>data-api-key</code> — chave pública do tenant (obrigatória quando configurada)</li>
        </ul>
        <div class="hint">
            A conversa é salva em <code>localStorage</code> por tenant. Use o botão ↺ no header do chat para iniciar uma nova conversa.
        </div>
    </div>
</div>

<script
    src="{{ url('/widget/agente.js') }}"
    data-tenant="default"
    data-position="bottom-right"
    data-primary-color="#1a1a2e"
    data-greeting="Olá! Como posso ajudar você hoje?"
    @if($apiKey) data-api-key="{{ $apiKey }}" @endif
    async
></script>

</body>
</html>
