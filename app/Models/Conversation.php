<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'source',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
        'model',
        'system_prompt',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Gera UUID automaticamente ao criar nova conversa
        static::creating(function (Conversation $conversation) {
            if (empty($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Retorna as últimas N mensagens para compor o contexto enviado à OpenAI.
     * Exclui mensagens do tipo 'system' pois o system prompt é enviado separadamente.
     */
    public function getContextMessages(int $limit): \Illuminate\Database\Eloquent\Collection
    {
        return $this->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }
}
