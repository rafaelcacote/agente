<?php

namespace App\Support;

class AllowedOrigin
{
    /**
     * Normaliza uma origem para comparação (scheme + host + port).
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = rtrim(trim($value), '/');

        if (! str_contains($value, '://')) {
            return strtolower($value);
        }

        $parts = parse_url($value);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $origin = strtolower($parts['scheme'].'://'.$parts['host']);

        if (! empty($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }

    /**
     * Extrai a origem de um header Referer.
     */
    public static function fromReferer(?string $referer): ?string
    {
        if ($referer === null || $referer === '') {
            return null;
        }

        $parts = parse_url($referer);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];

        if (! empty($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return self::normalize($origin);
    }

    /**
     * Verifica se a origem da requisição está na lista permitida.
     *
     * @param  array<int, string>  $allowed
     */
    public static function matches(?string $origin, array $allowed): bool
    {
        $normalized = self::normalize($origin);

        if ($normalized === null) {
            return false;
        }

        foreach ($allowed as $entry) {
            if (self::normalize($entry) === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $origins
     * @return array<int, string>
     */
    public static function normalizeList(array $origins): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($origin) => self::normalize(is_string($origin) ? $origin : null),
            $origins
        ))));
    }
}
