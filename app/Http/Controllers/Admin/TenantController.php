<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()
            ->withCount('conversations')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('admin.tenants.create', ['tenant' => new Tenant]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenant = Tenant::create($this->tenantAttributes($request, null));

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('success', 'Tenant criado com sucesso. Gere uma API Key para integrar o widget.');
    }

    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', [
            'tenant'           => $tenant,
            'allowedOrigins'   => $tenant->allowedOriginsAsText(),
            'embedSnippet'     => $tenant->buildEmbedSnippet(),
            'newApiKey'        => session('new_api_key'),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($this->tenantAttributes($request, $tenant));

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('success', 'Tenant atualizado.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        if ($tenant->conversations()->exists()) {
            return back()->with('error', 'Não é possível excluir um tenant com conversas. Desative-o em vez disso.');
        }

        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant excluído.');
    }

    public function rotateApiKey(Tenant $tenant): RedirectResponse
    {
        $plainKey = $tenant->assignApiKey();

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('success', 'Nova API Key gerada. Copie agora — ela não será exibida novamente.')
            ->with('new_api_key', $plainKey);
    }

    public function revokeApiKey(Tenant $tenant): RedirectResponse
    {
        $tenant->forceFill([
            'api_key_hash'   => null,
            'api_key_prefix' => null,
        ])->save();

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('success', 'API Key revogada.');
    }

    /** @return array<string, mixed> */
    private function tenantAttributes(StoreTenantRequest|UpdateTenantRequest $request, ?Tenant $existing): array
    {
        $settings = is_array($existing?->settings) ? $existing->settings : [];

        $settings['allowed_origins'] = Tenant::parseOriginsFromText($request->input('allowed_origins', ''));

        if ($request->filled('widget_primary_color')) {
            $settings['widget_primary_color'] = $request->input('widget_primary_color');
        }

        if ($request->filled('widget_greeting')) {
            $settings['widget_greeting'] = $request->input('widget_greeting');
        }

        return [
            'name'          => $request->string('name')->trim()->value(),
            'slug'          => $request->string('slug')->trim()->value(),
            'is_active'     => $request->boolean('is_active'),
            'system_prompt' => $request->input('system_prompt'),
            'settings'      => $settings,
        ];
    }
}
