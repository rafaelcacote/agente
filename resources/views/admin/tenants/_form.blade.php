<div class="form-row">
    <div class="form-group">
        <label for="name">Nome *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
        @error('name')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label for="slug">Slug *</label>
        <input type="text" id="slug" name="slug" value="{{ old('slug', $tenant->slug) }}" required pattern="[a-zA-Z0-9_-]+">
        <div class="hint">Usado em data-tenant no widget (ex: minha-empresa)</div>
        @error('slug')<div class="error">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-group form-check">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $tenant->is_active ?? true))>
    <label for="is_active">Tenant ativo</label>
</div>

<div class="form-group">
    <label for="system_prompt">System prompt</label>
    <textarea id="system_prompt" name="system_prompt" rows="10">{{ old('system_prompt', $tenant->system_prompt) }}</textarea>
    <div class="hint">Instrução base do assistente para este cliente. Vazio usa o padrão do .env.</div>
    @error('system_prompt')<div class="error">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label for="allowed_origins">Domínios permitidos (CORS)</label>
    <textarea id="allowed_origins" name="allowed_origins" rows="4" placeholder="https://loja.com&#10;https://www.loja.com">{{ old('allowed_origins', $allowedOrigins ?? $tenant->allowedOriginsAsText()) }}</textarea>
    <div class="hint">Uma origem por linha. Vazio = sem restrição de origem.</div>
    @error('allowed_origins')<div class="error">{{ $message }}</div>@enderror
</div>

<div class="form-row">
    <div class="form-group">
        <label for="widget_primary_color">Cor do widget</label>
        <input type="text" id="widget_primary_color" name="widget_primary_color" value="{{ old('widget_primary_color', $tenant->settings['widget_primary_color'] ?? '#1a1a2e') }}" placeholder="#1a1a2e">
        @error('widget_primary_color')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label for="widget_greeting">Saudação do widget</label>
        <input type="text" id="widget_greeting" name="widget_greeting" value="{{ old('widget_greeting', $tenant->settings['widget_greeting'] ?? '') }}" placeholder="Olá! Como posso ajudar?">
        @error('widget_greeting')<div class="error">{{ $message }}</div>@enderror
    </div>
</div>
