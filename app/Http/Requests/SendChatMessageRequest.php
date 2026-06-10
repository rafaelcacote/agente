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

    protected function prepareForValidation(): void
    {
        if (! $this->has('metadata') || ! is_array($this->input('metadata'))) {
            return;
        }

        $maxKeys = config('chat.metadata.max_keys', 10);
        $maxLength = config('chat.metadata.max_value_length', 255);

        $metadata = [];

        foreach (array_slice($this->input('metadata'), 0, $maxKeys, true) as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $key = strip_tags($key);

            if (strlen($key) > 100) {
                continue;
            }

            if ($value === null) {
                continue;
            }

            if (! is_scalar($value)) {
                continue;
            }

            $value = strip_tags((string) $value);
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
            $value = trim($value);

            if ($value === '') {
                continue;
            }

            $metadata[$key] = mb_substr($value, 0, $maxLength);
        }

        $this->merge(['metadata' => $metadata === [] ? null : $metadata]);
    }

    public function rules(): array
    {
        $maxKeys = config('chat.metadata.max_keys', 10);
        $maxLength = config('chat.metadata.max_value_length', 255);

        return [
            'conversation_uuid' => ['nullable', 'string', 'uuid', 'exists:conversations,uuid'],

            'tenant_slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::exists('tenants', 'slug')->where('is_active', true),
            ],

            'message' => ['required', 'string', 'min:1', 'max:4000'],

            'source' => ['nullable', 'string', 'max:50'],

            'metadata' => ['nullable', 'array', "max:{$maxKeys}"],
            'metadata.*' => ['nullable', 'string', "max:{$maxLength}"],
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
            'metadata.max'             => 'Metadados excedem o número máximo de campos permitidos.',
        ];
    }
}
