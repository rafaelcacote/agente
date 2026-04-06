<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // UUID da conversa existente — null para iniciar nova conversa
            'conversation_uuid' => ['nullable', 'string', 'uuid', 'exists:conversations,uuid'],

            // Empresa (tenant) — na primeira mensagem define qual agente; na continuação pode omitir
            'tenant_slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::exists('tenants', 'slug')->where('is_active', true),
            ],

            // Mensagem do usuário: obrigatória, máximo de 4000 caracteres
            'message' => ['required', 'string', 'min:1', 'max:4000'],

            // Origem do chat: web, whatsapp, api, etc.
            'source' => ['nullable', 'string', 'max:50'],

            // Metadados opcionais: nome, email, página de origem, etc.
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'A mensagem não pode estar vazia.',
            'message.max'      => 'A mensagem não pode ter mais de 4000 caracteres.',
            'conversation_uuid.uuid'   => 'O identificador de conversa é inválido.',
            'conversation_uuid.exists' => 'A conversa informada não foi encontrada.',
            'tenant_slug.exists'       => 'O tenant informado não existe ou está inativo.',
        ];
    }
}
