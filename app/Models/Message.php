<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    public function isFromAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Retorna o array no formato esperado pela OpenAI Chat API.
     * EXTENSÃO FUTURA: incluir 'tool_calls' e 'tool_results' para function calling.
     */
    public function toOpenAIFormat(): array
    {
        return [
            'role'    => $this->role,
            'content' => $this->content,
        ];
    }
}
