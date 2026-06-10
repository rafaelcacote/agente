<?php

namespace App\Models;

use App\Services\Cors\AllowedOriginRegistry;
use App\Support\AllowedOrigin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'system_prompt',
        'settings',
        'api_key_hash',
        'api_key_prefix',
        'is_active',
    ];

    protected $hidden = [
        'api_key_hash',
    ];

    protected $casts = [
        'settings'  => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tenant $tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });

        static::saved(fn () => app(AllowedOriginRegistry::class)->flush());
        static::deleted(fn () => app(AllowedOriginRegistry::class)->flush());
    }

    /**
     * Domínios autorizados a consumir a API deste tenant (widget cross-origin).
     *
     * @return array<int, string>
     */
    public function getAllowedOrigins(): array
    {
        $origins = $this->settings['allowed_origins'] ?? [];

        if (! is_array($origins)) {
            return [];
        }

        return AllowedOrigin::normalizeList($origins);
    }

    public function hasApiKey(): bool
    {
        return filled($this->api_key_hash);
    }

    public function verifyApiKey(string $plainKey): bool
    {
        if (! $this->hasApiKey()) {
            return false;
        }

        return Hash::check($plainKey, $this->api_key_hash);
    }

    /**
     * Gera e persiste uma nova API Key. Retorna o valor em texto plano (única exibição).
     */
    public function assignApiKey(): string
    {
        $plainKey = 'ag_'.Str::lower(Str::random(40));

        $this->forceFill([
            'api_key_hash'   => Hash::make($plainKey),
            'api_key_prefix' => substr($plainKey, 0, 12),
        ])->save();

        return $plainKey;
    }

    public function setApiKeyFromPlain(string $plainKey): void
    {
        $this->forceFill([
            'api_key_hash'   => Hash::make($plainKey),
            'api_key_prefix' => substr($plainKey, 0, 12),
        ])->save();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Converte texto (uma origem por linha) em array normalizado.
     *
     * @return array<int, string>
     */
    public static function parseOriginsFromText(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        return AllowedOrigin::normalizeList(array_map('trim', $lines));
    }

    public function allowedOriginsAsText(): string
    {
        return implode("\n", $this->getAllowedOrigins());
    }

    public function buildEmbedSnippet(?string $apiKey = null): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $color = $this->settings['widget_primary_color'] ?? '#1a1a2e';
        $greeting = $this->settings['widget_greeting'] ?? 'Olá! Como posso ajudar?';

        $lines = [
            '<script',
            '  src="'.$baseUrl.'/widget/agente.js"',
            '  data-tenant="'.$this->slug.'"',
            '  data-position="bottom-right"',
            '  data-primary-color="'.$color.'"',
            '  data-greeting="'.e($greeting).'"',
        ];

        if ($apiKey !== null && $apiKey !== '') {
            $lines[] = '  data-api-key="'.$apiKey.'"';
        } else {
            $lines[] = '  data-api-key="SUA_API_KEY_AQUI"';
        }

        $lines[] = '  async';
        $lines[] = '></script>';

        return implode("\n", $lines);
    }
}
