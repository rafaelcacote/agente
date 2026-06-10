<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id;

        return [
            'name'          => ['required', 'string', 'max:150'],
            'slug'          => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenantId)],
            'is_active'     => ['sometimes', 'boolean'],
            'system_prompt' => ['nullable', 'string', 'max:20000'],
            'allowed_origins' => ['nullable', 'string', 'max:5000'],
            'widget_primary_color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'widget_greeting'      => ['nullable', 'string', 'max:500'],
        ];
    }
}
